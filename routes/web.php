<?php

use App\Http\Controllers\Athlete\BookingsController;
use App\Http\Controllers\Athlete\ProfileController as AthleteProfileController;
use App\Http\Controllers\Coach\DashboardController;
use App\Http\Controllers\Coach\PaymentsController;
use App\Http\Controllers\Coach\ProfileController as CoachProfileController;
use App\Http\Controllers\Coach\WorkoutController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicMapController;
use Illuminate\Support\Facades\Route;


// TODO: remove after SMS debug
Route::get('/debug/sms-config', function () {
    $config = config('services.smscru');
    return response()->json([
        'login' => $config['login'] ?? null,
        'apikey_length' => isset($config['apikey']) ? strlen($config['apikey']) : 0,
        'apikey_first5' => isset($config['apikey']) ? substr($config['apikey'], 0, 5) : null,
        'apikey_last5' => isset($config['apikey']) ? substr($config['apikey'], -5) : null,
        'apikey_has_dollar' => isset($config['apikey']) && str_contains($config['apikey'], '$'),
        'password_set' => ! empty($config['password'] ?? null),
        'sender' => $config['sender'] ?? null,
        'force' => $config['force'] ?? null,
        'app_env' => app()->environment(),
        'provider' => get_class(app(\App\Services\Sms\SmsProviderInterface::class)),
    ]);
});

Route::get('/', [PublicMapController::class, 'index'])->name('home');
Route::get('/map', [PublicMapController::class, 'index'])->name('map');

Route::get('/dashboard', function () {
    $user = auth()->user();

    return redirect(match ($user->role) {
        'athlete' => route('athlete.bookings'),
        'coach' => route('coach.dashboard'),
        'admin' => route('admin.dashboard'),
        default => '/',
    });
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Onboarding routes
Route::middleware('auth')->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// Athlete routes
Route::middleware(['auth', 'role:athlete', 'ensure.profile.completed'])->prefix('athlete')->name('athlete.')->group(function () {
    Route::get('/bookings', [BookingsController::class, 'index'])->name('bookings');
    Route::get('/bookings/{booking}', [BookingsController::class, 'show'])->name('bookings.show');
    Route::get('/profile', [AthleteProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [AthleteProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [AthleteProfileController::class, 'uploadAvatar'])->name('profile.uploadAvatar');
    Route::delete('/profile/avatar', [AthleteProfileController::class, 'deleteAvatar'])->name('profile.deleteAvatar');
});

// Coach routes
Route::middleware(['auth', 'role:coach', 'ensure.profile.completed'])->prefix('coach')->name('coach.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [CoachProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [CoachProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [CoachProfileController::class, 'uploadAvatar'])->name('profile.uploadAvatar');
    Route::delete('/profile/avatar', [CoachProfileController::class, 'deleteAvatar'])->name('profile.deleteAvatar');
    Route::post('/profile/diploma', [CoachProfileController::class, 'uploadDiploma'])->name('profile.uploadDiploma');
    Route::delete('/profile/diploma/{mediaId}', [CoachProfileController::class, 'deleteDiploma'])->name('profile.deleteDiploma');
    Route::post('/profile/certificate', [CoachProfileController::class, 'uploadCertificate'])->name('profile.uploadCertificate');
    Route::delete('/profile/certificate/{mediaId}', [CoachProfileController::class, 'deleteCertificate'])->name('profile.deleteCertificate');
    Route::post('/profile/resubmit', [CoachProfileController::class, 'resubmit'])->name('profile.resubmit');
    Route::get('/payments', [PaymentsController::class, 'index'])->name('payments');

    // Workout routes
    Route::get('/workouts', [WorkoutController::class, 'index'])->name('workouts.index');
    Route::get('/workouts/create', [WorkoutController::class, 'create'])->name('workouts.create');
    Route::post('/workouts', [WorkoutController::class, 'store'])->name('workouts.store');
    Route::get('/workouts/{workout}/edit', [WorkoutController::class, 'edit'])->name('workouts.edit');
    Route::patch('/workouts/{workout}', [WorkoutController::class, 'update'])->name('workouts.update');
    Route::post('/workouts/{workout}/publish', [WorkoutController::class, 'publish'])->name('workouts.publish');
    Route::post('/workouts/{workout}/cancel', [WorkoutController::class, 'cancel'])->name('workouts.cancel');
});

// Admin routes (stub for future)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';
