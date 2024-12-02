<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;

    Route::post('/login', [UserController::class, 'login']);
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/verify-token', [UserController::class, 'verifyToken']);

    // Rutas protegidas (requieren autenticación)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [UserController::class, 'verifyToken']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });