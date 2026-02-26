@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Digitalisation des Produits</h1>
        <div id="auth-buttons">
            @if(auth()->check())
                <span class="me-3 fw-bold">Bienvenue {{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit" class="btn btn-danger">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login.form') }}" class="btn btn-primary" id="loginBtn">Connexion</a>
                <a href="{{ route('register.form') }}" class="btn btn-outline-secondary ms-2">Inscription</a>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!@json(auth()->check()) && localStorage.getItem('jwt_token')) {
                let authButtons = document.getElementById('auth-buttons');
                let token = localStorage.getItem('jwt_token');
                let name = extractNameFromJWT(token);
                let displayName = name && name.length > 0 ? name : 'Utilisateur';
                authButtons.innerHTML = `<button class='btn btn-primary fw-bold me-2'>Bienvenue ${displayName}</button>` +
                    `<form method='POST' action='{{ route('logout') }}' id='logoutForm' style='display:inline-block;'>` +
                    `<input type='hidden' name='_token' value='{{ csrf_token() }}'>` +
                    `<button type='submit' class='btn btn-danger ms-2'>Déconnexion</button>` +
                    `</form>`;
            }
            let logoutForm = document.getElementById('logoutForm');
            if (logoutForm) {
                logoutForm.addEventListener('submit', function() {
                    localStorage.removeItem('jwt_token');
                });
            }
        });
        function extractNameFromJWT(token) {
            if (!token) return '';
            let segments = token.split('.');
            if (segments.length !== 3) return '';
            let payload = segments[1];
            try {
                let decoded = JSON.parse(atob(payload));
                return decoded.full_name || decoded.nom || '';
            } catch (e) {
                return '';
            }
        }

    </script>

    @if(session('success'))
        <div class="alert alert-success text-center" style="max-width:600px;margin:0 auto;">
            @php
                $token = null;
                $message = session('success');
                if (strpos($message, 'Token : ') !== false) {
                    $parts = explode('Token : ', $message);
                    $token = trim($parts[1]);
                }
                $name = '';
                $email = '';
                if ($token) {
                    $segments = explode('.', $token);
                    if (count($segments) === 3) {
                        $payload = $segments[1];
                        $payloadDecoded = json_decode(base64_decode($payload), true);
                        if ($payloadDecoded) {
                            $name = $payloadDecoded['full_name'] ?? ($payloadDecoded['nom'] ?? '');
                            $email = $payloadDecoded['email'] ?? '';
                        }
                    }
                }
            @endphp
            @if($name && $email)
                <strong>Bienvenue {{ $name }}</strong><br>
                <span>Email : {{ $email }}</span>
            @else
                {{ session('success') }}
            @endif
        </div>
        <script>
            @if($token)
                localStorage.setItem('jwt_token', '{{ $token }}');
            @endif
        </script>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="" class="mb-4">
        <div class="row">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Rechercher un produit...">
            </div>
            <div class="col-md-4">
                <select name="category" class="form-control">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">Filtrer</button>
            </div>
        </div>
    </form>

    <div class="row">
        @forelse($products as $product)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="{{ $product->images->first() ? asset($product->images->first()->path) : asset('images/default.png') }}" class="card-img-top" alt="Image produit">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->name }}</h5>
                        <p class="card-text">Prix : <strong>{{ $product->price }} FCFA</strong></p>
                        <p class="card-text">Stock : {{ $product->stock }}</p>
                        <p class="card-text">Catégorie : {{ $product->category->name }}</p>
                        <a href="{{ route('products.showWb', $product->id) }}" class="btn btn-outline-primary">Voir détails</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Aucun produit trouvé.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('footer')
<footer class="bg-light text-center py-3 mt-5">
    <small>&copy; {{ date('Y') }} Gestion Stock. Contact : hello@example.com</small>
</footer>
@endsection
