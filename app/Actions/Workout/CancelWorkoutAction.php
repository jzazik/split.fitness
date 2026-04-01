<?php

namespace App\Actions\Workout;

use App\Models\Workout;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CancelWorkoutAction
{
    /**
     * Cancel a workout.
     *
     * @param Workout $workout
     * @return void
     * @throws ValidationException
     */
    public function execute(Workout $workout): void
    {
        // Check if already cancelled
        if ($workout->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Тренировка уже отменена.',
            ]);
        }

        // Check if there are any paid bookings
        // Note: Skip check if Booking model doesn't exist yet (will be added in Sprint 4)
        if (class_exists(\App\Models\Booking::class)) {
            $paidBookingsCount = $workout->bookings()
                ->where('payment_status', 'paid')
                ->count();

            if ($paidBookingsCount > 0) {
                Log::error('Attempted to cancel workout with paid bookings', [
                    'workout_id' => $workout->id,
                    'coach_id' => $workout->coach_id,
                    'paid_bookings_count' => $paidBookingsCount,
                ]);

                throw ValidationException::withMessages([
                    'bookings' => 'Нельзя отменить тренировку, есть оплаченные записи.',
                ]);
            }
        }

        // Update workout status
        $workout->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Log::info('Workout cancelled', [
            'workout_id' => $workout->id,
            'coach_id' => $workout->coach_id,
            'status' => 'cancelled',
            'starts_at' => $workout->starts_at->toIso8601String(),
            'slots_total' => $workout->slots_total,
            'slots_booked' => $workout->slots_booked,
        ]);
    }
}
