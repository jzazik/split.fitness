<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CloudPaymentsWebhookController;
use App\Http\Controllers\Api\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/workouts/map', [MapController::class, 'index'])
    ->middleware('throttle:60,1');

Route::middleware(['auth:sanctum', 'role:athlete,coach'])->group(function () {
    Route::post('/bookings', [BookingController::class, 'store'])
        ->middleware('throttle:10,1');
});

Route::prefix('payments/cloudpayments')->group(function () {
    Route::post('/pay', [CloudPaymentsWebhookController::class, 'pay']);
    Route::post('/fail', [CloudPaymentsWebhookController::class, 'fail']);
});
