<?php

use App\Http\Controllers\Api\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/workouts/map', [MapController::class, 'index'])
    ->middleware('throttle:60,1'); // 60 requests per minute
