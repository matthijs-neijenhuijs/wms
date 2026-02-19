<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')
    ->prefix('v1')
    ->group(function () {
        Route::apiResource('products', ProductController::class)->only(['index', 'show']);
        Route::apiResource('clients', ClientController::class)->only(['index', 'show']);
        Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
    });
