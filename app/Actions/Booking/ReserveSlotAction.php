<?php

namespace App\Actions\Booking;

use App\Exceptions\Booking\OversellException;
use App\Models\Workout;
use Illuminate\Support\Facades\Log;

class ReserveSlotAction
{
    /**
     * Reserve a slot for a workout with oversell protection.
     * Note: This method is called within a transaction from CreateBookingAction.
     *
     * @throws OversellException
     */
    public function execute(Workout $workout, int $slotsCount = 1, ?string $transactionId = null): void
    {
        // Re-fetch the workout with a pessimistic lock to prevent race conditions
        $workout = Workout::where('id', $workout->id)->lockForUpdate()->first();

        $slotsBefore = $workout->slots_booked;
        $slotsAfter = $slotsBefore + $slotsCount;

        if ($slotsAfter > $workout->slots_total) {
            Log::error('Slot reservation failed: no available slots', [
                'workout_id' => $workout->id,
                'slots_requested' => $slotsCount,
                'slots_booked' => $slotsBefore,
                'slots_total' => $workout->slots_total,
                'slots_available' => $workout->slots_total - $slotsBefore,
                'transaction_id' => $transactionId,
            ]);

            throw new OversellException(
                workoutId: $workout->id,
                slotsRequested: $slotsCount,
                slotsBooked: $slotsBefore,
                slotsTotal: $workout->slots_total
            );
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
                'transaction_id' => $transactionId,
            ]);

            throw new OversellException(
                workoutId: $workout->id,
                slotsRequested: $slotsCount,
                slotsBooked: $workout->slots_booked,
                slotsTotal: $workout->slots_total,
                message: 'КРИТИЧЕСКАЯ ОШИБКА: Обнаружена пересадка слотов.'
            );
        }

        Log::info('Slot reserved successfully', [
            'workout_id' => $workout->id,
            'slots_count' => $slotsCount,
            'slots_booked_before' => $slotsBefore,
            'slots_booked_after' => $workout->slots_booked,
            'slots_total' => $workout->slots_total,
            'transaction_id' => $transactionId,
        ]);
    }
}
