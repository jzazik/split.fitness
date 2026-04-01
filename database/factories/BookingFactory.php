<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slotsCount = 1;
        $slotPrice = $this->faker->randomFloat(2, 500, 2000);
        $totalAmount = $slotPrice * $slotsCount;

        return [
            'workout_id' => Workout::factory()->published(),
            'athlete_id' => User::factory()->athlete(),
            'slots_count' => $slotsCount,
            'slot_price' => $slotPrice,
            'total_amount' => $totalAmount,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'booked_at' => now(),
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    /**
     * Indicate that the booking is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Indicate that the booking is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelled by athlete',
        ]);
    }

    /**
     * Indicate that the booking is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);
    }
}
