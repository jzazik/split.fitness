<?php

use App\Http\Controllers\Api\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/workouts/map', [MapController::class, 'index']);
