@extends('layouts.app')

@section('content')
<div class="container mt-5 d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="w-100" style="max-width: 400px;">
        <h2 class="text-center mb-4">Inscription</h2>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Nom complet</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="telephone" class="form-label">Téléphone</label>
                <input type="text" class="form-control" id="telephone" name="telephone">
            </div>
            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse</label>
                <input type="text" class="form-control" id="adresse" name="adresse">
            </div>
            <div class="mb-3">
                <label for="pays" class="form-label">Pays</label>
                <input type="text" class="form-control" id="pays" name="pays">
            </div>
            <div class="mb-3">
                <label for="profession" class="form-label">Profession</label>
                <input type="text" class="form-control" id="profession" name="profession">
            </div>
            <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
            <a href="{{ route('login.form') }}" class="btn btn-link w-100 text-center mt-2">Déjà inscrit ? Se connecter</a>
        </form>
    </div>
</div>
@endsection
