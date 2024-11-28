<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;


Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart', [CartController::class, 'addToCart']);
Route::put('/cart/{id}', [CartController::class, 'updateCart']);
Route::post('/cart/checkout', [CartController::class, 'checkout']);
