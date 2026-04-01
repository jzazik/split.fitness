<?php

namespace App\Actions\Booking;

use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateBookingAction
{
    public function __construct(
        protected ReserveSlotAction $reserveSlotAction
    ) {}

    /**
     * Create a booking for a workout.
     */
    public function execute(Workout $workout, User $athlete, int $slotsCount = 1): Booking
    {
        return DB::transaction(function () use ($workout, $athlete, $slotsCount) {
            // Check for duplicate booking within transaction
            $existingBooking = Booking::where('workout_id', $workout->id)
                ->where('athlete_id', $athlete->id)
                ->whereIn('status', ['pending_payment', 'paid'])
                ->lockForUpdate()
                ->exists();

            if ($existingBooking) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Вы уже записаны на эту тренировку',
                ]);
            }

            $slotsBefore = $workout->slots_booked;
            $this->reserveSlotAction->execute($workout, $slotsCount);
            $workout->refresh();
            $slotsAfter = $workout->slots_booked;

            $slotPrice = $workout->slot_price;
            $totalAmount = $slotPrice * $slotsCount;

            $booking = Booking::create([
                'workout_id' => $workout->id,
                'athlete_id' => $athlete->id,
                'slots_count' => $slotsCount,
                'slot_price' => $slotPrice,
                'total_amount' => $totalAmount,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'booked_at' => now(),
            ]);

            Log::info('Booking created', [
                'booking_id' => $booking->id,
                'workout_id' => $workout->id,
                'athlete_id' => $athlete->id,
                'slots_count' => $slotsCount,
                'status' => $booking->status,
                'total_amount' => $totalAmount,
                'slots_booked_before' => $slotsBefore,
                'slots_booked_after' => $slotsAfter,
            ]);

            BookingCreated::dispatch($booking);

            return $booking;
        });
    }
}
