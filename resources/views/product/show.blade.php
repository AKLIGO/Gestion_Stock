@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card mb-4">
        <div class="row g-0">
            <div class="col-md-4">
                <img src="{{ $product->images->first() ? asset($product->images->first()->path) : asset('images/default.png') }}" class="img-fluid rounded-start" alt="Image produit">
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h3 class="card-title">{{ $product->name }}</h3>
                    <p class="card-text">Prix : <strong>{{ $product->price }} FCFA</strong></p>
                    <p class="card-text">Stock : {{ $product->stock }}</p>
                    <p class="card-text">Catégorie : {{ $product->category->name }}</p>
                    <p class="card-text">Description : {{ $product->description }}</p>
                    <a href="{{ url()->previous() }}" class="btn btn-secondary mt-3">Retour</a>
                    <!-- Bouton Payer ouvre le formulaire modal -->
                    <button type="button" class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#payModal">Acheter</button>

                    <!-- Modal de paiement -->
                    <div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="payModalLabel">Choisir la quantité à payer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <form method="POST" action="{{ route('pay.product', $product->id) }}">
                            @csrf
                            <div class="modal-body">
                              <div class="mb-3">
                                <label for="quantity" class="form-label">Quantité</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="{{ $product->stock }}" value="1" required>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                              <button type="submit" class="btn btn-success">Valider</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <h5>Images supplémentaires :</h5>
        @foreach($product->images as $image)
            <div class="col-md-3 mb-3">
                <img src="{{ asset($image->path) }}" class="img-thumbnail" alt="Image produit">
            </div>
        @endforeach
    </div>
</div>
@endsection

@if(session('success'))
    <div class="alert alert-success mt-3">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
@endif
