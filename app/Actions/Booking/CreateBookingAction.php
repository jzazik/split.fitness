<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\Log;

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
        $this->reserveSlotAction->execute($workout, $slotsCount);

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
        ]);

        return $booking;
    }
}
