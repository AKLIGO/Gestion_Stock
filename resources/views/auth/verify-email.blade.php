@extends('layouts.app')

@section('content')
<div class="container mt-5 d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="w-100" style="max-width: 400px;">
        <h2 class="text-center mb-4">Vérification du code</h2>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('verify.email') }}">
            @csrf
            <div class="mb-3">
                <label for="code" class="form-label">Code reçu par email</label>
                <input type="text" class="form-control" id="code" name="code" required maxlength="6">
            </div>
            <button type="submit" class="btn btn-success w-100">Valider</button>
        </form>
    </div>
</div>
@endsection
