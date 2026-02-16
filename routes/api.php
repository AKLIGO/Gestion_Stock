<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuthController;
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {

    // Public
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verifycode', [AuthController::class, 'verifyCode']);

    // Protégé par JWT
    Route::middleware('auth:api')->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

});

Route::prefix('roles')->group(function () {

    // Créer un rôle (store)
    Route::post('/', [RoleController::class, 'store']);
    // ->middleware('auth:api');
});