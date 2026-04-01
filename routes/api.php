<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/workouts/map', [MapController::class, 'index'])
    ->middleware('throttle:60,1'); // 60 requests per minute

Route::middleware(['auth:sanctum', 'role:athlete'])->group(function () {
    Route::post('/bookings', [BookingController::class, 'store']);
});
