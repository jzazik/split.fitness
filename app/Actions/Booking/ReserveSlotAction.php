<?php

namespace App\Actions\Booking;

use App\Models\Workout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReserveSlotAction
{
    /**
     * Reserve a slot for a workout with oversell protection.
     *
     * @throws ValidationException
     */
    public function execute(Workout $workout, int $slotsCount = 1): void
    {
        DB::transaction(function () use ($workout, $slotsCount) {
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

            Log::info('Slot reserved successfully', [
                'workout_id' => $workout->id,
                'slots_count' => $slotsCount,
                'slots_booked_before' => $slotsBefore,
                'slots_booked_after' => $slotsAfter,
                'slots_total' => $workout->slots_total,
            ]);
        });
    }
}
