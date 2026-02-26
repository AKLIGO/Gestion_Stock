<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Wrb\AuthController;



Route::get('/', [ProductController::class, 'indexWeb'])->name('home');
Route::get('/productsWeb/{product}', [ProductController::class, 'showWeb'])->name('products.showWb');
Route::post('/productsWeb/{product}/pay', [ProductController::class, 'payWeb'])->name('pay.product');

Route::middleware(['auth'])->group(function () {
   Route::get('/purchases/{product  }', [ProductController::class, 'purchase'])->name('products.purchase');
   
});

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/verify-email', [AuthController::class, 'showVerifyEmailForm'])->name('verify.email.form');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('verify.email');