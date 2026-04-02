<?php

namespace App\Providers;

use App\Events\BookingCreated;
use App\Listeners\NotifyCoachNewBooking;
use App\Models\Booking;
use App\Models\Workout;
use App\Policies\BookingPolicy;
use App\Policies\WorkoutPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register policies
        Gate::policy(Workout::class, WorkoutPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);

        LogViewer::auth(fn ($request) => true);

        // Register event listeners
        Event::listen(
            BookingCreated::class,
            NotifyCoachNewBooking::class,
        );
    }
}
