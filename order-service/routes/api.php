<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::get('/health', function () {
    return response()->json([
        'service' => 'order-service',
        'status' => 'healthy',
    ]);
});

Route::post('/orders', [OrderController::class, 'checkout']);

// Route user harus diletakkan sebelum route ID
Route::get('/orders/user/{userId}', [OrderController::class, 'userOrders']);

// ID pesanan hanya boleh berupa angka
Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->whereNumber('id');

Route::patch('/orders/{id}/pay', [OrderController::class, 'pay'])
    ->whereNumber('id');