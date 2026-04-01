<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyCoachNewBooking implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking->load('workout.coach', 'athlete');

        if (! $booking->workout || ! $booking->workout->coach) {
            Log::warning('Cannot notify coach for new booking: workout or coach not found', [
                'booking_id' => $booking->id,
                'workout_id' => $booking->workout_id,
            ]);

            return;
        }

        Log::info('Coach notification queued for new booking', [
            'booking_id' => $booking->id,
            'workout_id' => $booking->workout_id,
            'coach_id' => $booking->workout->coach_id,
            'athlete_id' => $booking->athlete_id,
        ]);

        // TODO: Sprint 7 - implement email notification to coach
        // Mail::to($booking->workout->coach)->send(new BookingConfirmationForCoach($booking));
    }
}
