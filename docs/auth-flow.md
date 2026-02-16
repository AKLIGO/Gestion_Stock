# Guide du flux Auth & JWT

## Commandes essentielles
- `composer install` — installe les dépendances PHP du projet.
- `composer require tymon/jwt-auth` — ajoute la librairie JWTAuth au projet.
- `php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"` — publie la config et les assets du package JWT.
- `cp .env.example .env` — crée le fichier d’environnement à partir du modèle.
- `php artisan key:generate` — génère la clé d’application Laravel.
- `php artisan jwt:secret` — crée la clé secrète utilisée pour signer les JWT.
- Configurer `.env` (base de données + mail) — renseigne les accès DB et les paramètres d’envoi de mails.
- `php artisan migrate` — exécute les migrations pour créer les tables.
- `php artisan db:seed` *(optionnel)* — insère des données de départ si des seeders existent.
- `php artisan install:api` — installe les dépendances API Laravel (sanctum/pest selon version).
- `php artisan make:request RoleRequest` — génère la classe de Form Request pour valider les rôles.
- `php artisan serve` — lance le serveur HTTP de développement.
- `php artisan test` — exécute la suite de tests PHPUnit/Pest.
- `npm install` *(si front-end géré avec Vite)* — installe les dépendances JS/Front.
- `npm run dev` *(ou `npm run build` en prod)* — compile les assets front.

Ce document décrit, étape par étape, le parcours d'authentification mis en place : de l'inscription jusqu'à la génération du jeton JWT, en détaillant les imports utilisés et la logique de chaque bloc de code.

## 1. Inscription (`POST /api/auth/register`)

### Méthode du contrôleur
Source : [app/Http/Controllers/AuthController.php](../app/Http/Controllers/AuthController.php#L18-L38)

```php
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
```

**Fonctionnement**
- Les données validées arrivent via `UserRequest`.
- Le modèle `User` autorise l'assignation de masse des champs déclarés dans `$fillable`.
- Le mot de passe est chiffré avec `bcrypt()` avant enregistrement.
- Le bloc `if ($defaultRole = Role::where('name', 'user')->first()) { ... }` cherche un rôle `user`; si trouvé, il est attaché via `syncWithoutDetaching`, ce qui ajoute l'identifiant du rôle sans détacher ceux déjà présents.
- La réponse HTTP 201 renvoie un message de confirmation et les informations de base du nouvel utilisateur.

### Imports associés
- `use App\Http\Requests\UserRequest;` — fournit validation et autorisation typées.
- `use App\Models\User;` — modèle Eloquent qui persiste l'utilisateur.
- `use App\Models\Role;` — utilisé pour récupérer le rôle par défaut.

### Règles de validation
Source : [app/Http/Requests/UserRequest.php](../app/Http/Requests/UserRequest.php)

- `name`, `email` et `password` (confirmé) sont obligatoires.
- L'unicité de l'email est garantie; les autres champs sont optionnels mais contrôlés.
- `authorize()` retourne `true`, l'accès est donc ouvert.

## 2. Demande de code (`POST /api/auth/login`)

### Méthode du contrôleur
Source : [app/Http/Controllers/AuthController.php](../app/Http/Controllers/AuthController.php#L40-L88)

```php
$user = User::where('email', $request->email)->first();
if (!$user || !Hash::check($request->password, $user->password)) {
    return response()->json(['message' => 'Invalid credentials'], 401);
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
```

**Fonctionnement**
- Le système recherche un utilisateur correspondant à l'email fourni et vérifie le mot de passe avec `Hash::check`.
- Les anciens codes non consommés sont supprimés pour empêcher la réutilisation.
- Un code aléatoire à 6 chiffres est généré puis haché avant stockage.
- Le code est enregistré dans la table `login_codes` avec une expiration de 10 minutes.
- Un email est envoyé via le mailable `LoginCodeMail`.
- Un log est écrit pour audit (sans exposer le code).
- En environnement non production, le code est aussi renvoyé dans la réponse JSON pour faciliter les tests.

### Imports associés
- `use App\Http\Requests\LoginRequest;` — valide le format de l'email.
- `use App\Models\LoginCode;` — modèle Eloquent chargé des codes temporaires.
- `use App\Mail\LoginCodeMail;` — mailable responsable de l'email.
- `use Illuminate\Support\Facades\Hash;` — hachage du code avant stockage.
- `use Illuminate\Support\Facades\Mail;` — envoi du courriel.
- `use Illuminate\Support\Facades\Log;` — journalisation de l'événement.

### Règles de validation
Source : [app/Http/Requests/LoginRequest.php](../app/Http/Requests/LoginRequest.php)

- Les champs `email` et `password` sont requis (`required|email`, `required|string`).
- `authorize()` retourne `true`, la route reste publique.

### Modèle d'email
Source : [resources/views/emails/login_code.blade.php](../resources/views/emails/login_code.blade.php)

- Affiche le code et rappelle son expiration à 10 minutes.

### Mailable
Source : [app/Mail/LoginCodeMail.php](../app/Mail/LoginCodeMail.php)

- Son constructeur accepte le code et le nom et expose ces valeurs en propriétés publiques :

```php
public function __construct(
    public string $code,
    public ?string $name
) {
}
```

- La méthode `build()` fixe l'objet, choisit la vue `emails.login_code` et transmet les données à la vue :

```php
public function build(): self
{
    return $this
        ->subject('Code de vérification')
        ->view('emails.login_code')
        ->with([
            'code' => $this->code,
            'name' => $this->name,
        ]);
}
```

## 3. Vérification et émission du JWT (`POST /api/auth/verifycode`)

### Méthode du contrôleur
Source : [app/Http/Controllers/AuthController.php](../app/Http/Controllers/AuthController.php#L90-L129)

```php
$loginCode = LoginCode::where('user_id', $user->id)
    ->orderByDesc('created_at')
    ->first();

if (!$loginCode || $loginCode->consumed_at !== null || $loginCode->expires_at->isPast()) {
    return response()->json(['message' => 'Verification code expired'], 422);
}

if (!Hash::check($request->code, $loginCode->code_hash)) {
    return response()->json(['message' => 'Invalid verification code'], 422);
}

$loginCode->update(['consumed_at' => now()]);

$nameParts = $this->extractNameParts($user->name);
$claims = [
    'email' => $user->email,
    'nom' => $nameParts['nom'],
    'prenoms' => $nameParts['prenoms'],
];
$token = JWTAuth::claims($claims)->fromUser($user);
```

**Détail du code**
- Recherche le dernier code généré pour l’utilisateur via `where('user_id', ...)` et `orderByDesc('created_at')` ; `first()` récupère l’instance la plus récente.
- Retourne une erreur 422 si aucun code n’est trouvé, s’il a déjà été consommé (`consumed_at` non nul) ou s’il est expiré (`expires_at->isPast()`).
- Valide la combinaison email/code en comparant la saisie à la valeur hachée avec `Hash::check`; le moindre échec renvoie `422 Invalid verification code`.
- Met à jour `consumed_at` avec `now()` pour bloquer toute réutilisation du code.
- Décompose le nom complet avec `extractNameParts` puis construit le tableau `$claims` contenant l’email, le nom et les prénoms à injecter dans le JWT.
- Génère finalement le jeton via `JWTAuth::claims(...)->fromUser($user)` ce qui signe le token avec les claims personnalisés.

**Fonctionnement**
- Récupère le code le plus récent pour l'utilisateur.
- Refuse la requête si le code est introuvable, expiré ou déjà consommé.
- Valide la saisie via `Hash::check` contre le hachage stocké.
- Marque le code comme consommé pour éviter une seconde utilisation.
- Décompose `User::name` en `nom` et `prenoms` grâce à `extractNameParts`.
- Génère un JWT contenant les claims personnalisés `email`, `nom`, `prenoms`.
- Retourne le jeton et sa durée de vie (en secondes).

### Imports associés
- `use App\Http\Requests\VerifyCodeRequest;` — assure la présence de `email` et `code`.
- `use Tymon\JWTAuth\Facades\JWTAuth;` — génère le JWT avec claims personnalisés.
- `use Illuminate\Support\Facades\Hash;` — compare le code soumis et le hachage.

### Règles de validation
Source : [app/Http/Requests/VerifyCodeRequest.php](../app/Http/Requests/VerifyCodeRequest.php)

- Exige `email` et un `code` sur 6 chiffres.
- `authorize()` retourne `true`.

### Méthode utilitaire
Source : [app/Http/Controllers/AuthController.php](../app/Http/Controllers/AuthController.php#L131-L157)

- `extractNameParts()` découpe le nom complet pour alimenter les réponses et les claims JWT.

## 4. Profil et déconnexion

### Profil (`GET /api/auth/profile`)
Source : [app/Http/Controllers/AuthController.php](../app/Http/Controllers/AuthController.php#L90-L111)

- Renvoie l'identifiant, l'email, les noms dérivés et la liste des rôles de l'utilisateur authentifié.
- Nécessite le middleware `auth:api` (JWT obligatoire).

### Déconnexion (`POST /api/auth/logout`)
Source : [app/Http/Controllers/AuthController.php](../app/Http/Controllers/AuthController.php#L113-L121)

- Invalide le jeton courant via `auth('api')->logout()` (blacklist JWT).
- Retourne un message de confirmation.

## 5. Modèles et persistance

### Table `login_codes`
Source : [database/migrations/2026_02_10_120000_create_login_codes_table.php](../database/migrations/2026_02_10_120000_create_login_codes_table.php)

- Colonnes : `user_id`, `code_hash`, `expires_at`, `consumed_at`, timestamps.
- Index combiné sur `user_id` et `expires_at` pour des recherches rapides.
- Suppression en cascade si l'utilisateur est retiré.

### Modèle `LoginCode`
Source : [app/Models/LoginCode.php](../app/Models/LoginCode.php)

- `$fillable` aligné sur la migration.
- Casts automatiques des dates en instances Carbon.
- Scope `active` pour filtrer les codes valides.
- Relation `user()` (belongsTo) disponible.

### Modèle `User`
Source : [app/Models/User.php](../app/Models/User.php)

- `$fillable` inclut les champs de profil supplémentaires.
- Conserve la relation `roles()` utilisée à l'inscription et lors du profil.
- Implemente `JWTSubject` pour que JWTAuth puisse extraire l'identifiant utilisateur (`getJWTIdentifier()`) et ajouter des claims personnalisés (`getJWTCustomClaims()`), prérequis pour générer des tokens via `fromUser()`.

## 6. Routes
Source : [routes/api.php](../routes/api.php)

- `/api/auth/register` — POST, public.
- `/api/auth/login` — POST, public (email uniquement).
- `/api/auth/verifycode` — POST, public (email + code).
- `/api/auth/profile` — GET, protégé par JWT.
- `/api/auth/logout` — POST, protégé par JWT.

## 7. Configuration mail

- Renseigner `.env` avec `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, identifiants et expéditeur.
- Pour les tests, utiliser un service comme Mailtrap ou Mailhog.

## 8. Scénario de test recommandé

1. Lancer `php artisan migrate` pour appliquer la migration `login_codes`.
2. Créer un utilisateur via `/api/auth/register` et vérifier la présence du rôle.
3. Appeler `/api/auth/login` avec l'email et contrôler la réception du courriel et la création du code.
4. Soumettre `/api/auth/verifycode` avec le code reçu afin d'obtenir un JWT.
5. Utiliser le jeton pour accéder à `/api/auth/profile`, puis `/api/auth/logout`.

## 10. Tests Postman / Thunder Client

### Préparation
- Lancer `php artisan serve`.
- Vérifier la configuration mail (.env) ou utiliser Mailtrap/Mailhog.
- S'assurer que la migration `login_codes` est appliquée.

### 1. Inscription
- URL : `POST http://127.0.0.1:8000/api/auth/register`
- Headers : `Content-Type: application/json`
- Body :
```json
{
    "name": "Alice Doe",
    "email": "alice@example.com",
    "password": "secret123",
    "password_confirmation": "secret123",
    "telephone": "+221770000000",
    "adresse": "Dakar",
    "pays": "Sénégal",
    "profession": "Comptable"
}
```
- Attendu : HTTP 201, message "User registered successfully".

### 2. Demande de code
- URL : `POST http://127.0.0.1:8000/api/auth/login`
- Body :
```json
{
    "email": "alice@example.com",
    "password": "secret123"
}
```
- Attendu : HTTP 200 + message "Verification code sent". Récupérer le code par email (ou dans la réponse en local).

### 3. Vérification du code
- URL : `POST http://127.0.0.1:8000/api/auth/verifycode`
- Body :
```json
{
    "email": "alice@example.com",
    "code": "123456"
}
```
- Remplacer `123456` par le code reçu ; attendu : HTTP 200 avec `token` et `expires_in`.

### 4. Profil protégé
- URL : `GET http://127.0.0.1:8000/api/auth/profile`
- Headers : `Authorization: Bearer <token>` (jeton de l'étape 3)
- Attendu : HTTP 200 avec id, email, nom/prénoms et rôles.

### 5. Déconnexion
- URL : `POST http://127.0.0.1:8000/api/auth/logout`
- Headers : même jeton.
- Attendu : HTTP 200 message "Logged out successfully".

### Cas négatifs à vérifier
- Mot de passe erroné à `/api/auth/login` → HTTP 401.
- Code réutilisé ou expiré à `/api/auth/verifycode` → HTTP 422.
- Absence de jeton pour `/api/auth/profile` → HTTP 401.

## 9. Récapitulatif des erreurs gérées

- `404 User not found` — aucun utilisateur pour cet email (login ou vérification).
- `422 Verification code expired` — code absent, expiré ou déjà utilisé.
- `422 Invalid verification code` — code saisi incorrect.

Ce guide fournit une vision complète du flux d'authentification et des raisons derrière chaque import et bloc de code, afin de faciliter la maintenance et l'évolution du module.
