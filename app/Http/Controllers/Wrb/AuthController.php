<?php

namespace App\Http\Controllers\Wrb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'telephone' => 'nullable',
            'adresse' => 'nullable',
            'pays' => 'nullable',
            'profession' => 'nullable',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
            'pays' => $request->pays,
            'profession' => $request->profession,
        ]);
        if ($defaultRole = Role::where('name', 'user')->first()) {
            $user->roles()->syncWithoutDetaching($defaultRole->id);
        }
        return redirect()->route('login.form')->with('success', 'Inscription réussie. Veuillez vous connecter.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return redirect()->back()->with('error', 'Identifiants invalides');
        }
        // Générer un code de vérification
        $code = Str::random(6);
        session(['email_verification_code' => $code, 'email_verification_user_id' => $user->id]);
        Mail::raw('Votre code de vérification : ' . $code, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Vérification Email');
        });
        // Rediriger vers la page de saisie du code
        return redirect()->route('verify.email.form')->with('success', 'Code envoyé par email. Veuillez vérifier votre email.');
    }

    public function showVerifyEmailForm()
    {
        return view('auth.verify-email');
    }

    public function verifyEmail(Request $request)
    {
        $code = $request->input('code');
        $sessionCode = session('email_verification_code');
        $userId = session('email_verification_user_id');
        $user = User::find($userId);
        if ($code && $sessionCode && $code === $sessionCode && $user) {
            // Marquer l'utilisateur comme vérifié
            $user->email_verified_at = now();
            $user->save();
            session()->forget(['email_verification_code', 'email_verification_user_id']);
            // Générer le token JWT
            $token = JWTAuth::fromUser($user);
            // Authentifier l'utilisateur
            auth()->login($user);
            return redirect()->route('home')->with('success', 'Email vérifié. Token : ' . $token);
        }
        return redirect()->back()->with('error', 'Code de vérification invalide');
    }

    public function logout(Request $request)
    {
        // Si session Laravel, déconnexion
        if (auth()->check()) {
            auth()->logout();
        }
        // Toujours rediriger vers home
        return redirect()->route('home')->with('success', 'Déconnexion réussie');
    }

    public function sendVerificationEmail(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->back()->with('error', 'Utilisateur non authentifié');
        }
        // Générer un code unique
        $code = Str::random(6);
        // Stocker le code dans la session ou la base (simple: session)
        session(['email_verification_code' => $code]);
        // Envoyer l'email
        Mail::raw('Votre code de vérification : ' . $code, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Vérification Email');
        });
        Log::info('Code de vérification email envoyé', ['user_id' => $user->id, 'code' => $code]);
        return redirect()->back()->with('success', 'Code de vérification envoyé par email');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }
}
