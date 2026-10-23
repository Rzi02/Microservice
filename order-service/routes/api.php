<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'service' => 'order-service',
        'status' => 'healthy',
    ]);
});