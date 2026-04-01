<?php

namespace App\Actions\Booking;

use App\Models\Workout;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReserveSlotAction
{
    /**
     * Reserve a slot for a workout with oversell protection.
     * Note: This method is called within a transaction from CreateBookingAction.
     *
     * @throws ValidationException
     */
    public function execute(Workout $workout, int $slotsCount = 1): void
    {
        $workout->lockForUpdate();
        $workout->refresh();

        $slotsBefore = $workout->slots_booked;
        $slotsAfter = $slotsBefore + $slotsCount;

        if ($slotsAfter > $workout->slots_total) {
            Log::error('Slot reservation failed: no available slots', [
                'workout_id' => $workout->id,
                'slots_requested' => $slotsCount,
                'slots_booked' => $slotsBefore,
                'slots_total' => $workout->slots_total,
                'slots_available' => $workout->slots_total - $slotsBefore,
            ]);

            throw ValidationException::withMessages([
                'workout_id' => 'Недостаточно мест для бронирования.',
            ]);
        }

        $workout->increment('slots_booked', $slotsCount);

        // Check for oversell after increment (should never happen with proper locking)
        $workout->refresh();
        if ($workout->slots_booked > $workout->slots_total) {
            Log::critical('OVERSELL DETECTED: slots_booked exceeds slots_total', [
                'workout_id' => $workout->id,
                'slots_booked' => $workout->slots_booked,
                'slots_total' => $workout->slots_total,
                'slots_count' => $slotsCount,
                'slots_before' => $slotsBefore,
            ]);
        }

        Log::info('Slot reserved successfully', [
            'workout_id' => $workout->id,
            'slots_count' => $slotsCount,
            'slots_booked_before' => $slotsBefore,
            'slots_booked_after' => $workout->slots_booked,
            'slots_total' => $workout->slots_total,
        ]);
    }
}
