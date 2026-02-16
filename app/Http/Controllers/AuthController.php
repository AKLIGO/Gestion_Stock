<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Models\LoginCode;
use App\Mail\LoginCodeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    //
    public function register(UserRequest $request)
    {
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

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        LoginCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $loginCode = LoginCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new LoginCodeMail($code, $user->name));

        Log::info('Login verification code generated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'login_code_id' => $loginCode->id,
        ]);

        $payload = ['message' => 'Verification code sent'];

        if (!app()->environment('production')) {
            $payload['code'] = $code;
        }

        return response()->json($payload);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        $nameParts = $this->extractNameParts($user->name);

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'nom' => $nameParts['nom'],
            'prenoms' => $nameParts['prenoms'],
            'full_name' => $user->name,
            'roles' => $user->roles->pluck('name'),
        ]);
    }

    public function logout(Request $request)
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function verifyCode(VerifyCodeRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $loginCode = LoginCode::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$loginCode) {
            return response()->json([
                'message' => 'No verification code found',
            ], 422);
        }

        if ($loginCode->consumed_at !== null || $loginCode->expires_at->isPast()) {
            return response()->json([
                'message' => 'Verification code expired',
            ], 422);
        }

        if (!Hash::check($request->code, $loginCode->code_hash)) {
            return response()->json([
                'message' => 'Invalid verification code',
            ], 422);
        }

        $loginCode->update(['consumed_at' => now()]);

        $nameParts = $this->extractNameParts($user->name);

        $claims = [
            'email' => $user->email,
            'nom' => $nameParts['nom'],
            'prenoms' => $nameParts['prenoms'],
        ];

        $token = JWTAuth::claims($claims)->fromUser($user);

        return response()->json([
            'token' => $token,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    private function extractNameParts(?string $fullName): array
    {
        $fullName = trim((string) $fullName);

        if ($fullName === '') {
            return ['prenoms' => '', 'nom' => ''];
        }

        $segments = preg_split('/\s+/', $fullName) ?: [];

        if (count($segments) === 1) {
            return ['prenoms' => $segments[0], 'nom' => $segments[0]];
        }

        $nom = array_pop($segments);
        $prenoms = trim(implode(' ', $segments));

        return [
            'prenoms' => $prenoms !== '' ? $prenoms : $nom,
            'nom' => $nom,
        ];
    }

}
