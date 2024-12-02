<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::apiResource('/payments', PaymentController::class);
