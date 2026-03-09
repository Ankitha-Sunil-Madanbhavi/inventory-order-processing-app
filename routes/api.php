<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Products
Route::prefix('products')->group(function () {
    Route::get('/',          [ProductController::class, 'index']);
    Route::get('/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/{product}', [ProductController::class, 'show']);
});

// Orders
Route::post('/orders',                [OrderController::class, 'store']);
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

// User order history
Route::get('/users/{userId}/orders',  [OrderController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);