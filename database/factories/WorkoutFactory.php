<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Sport;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalPrice = $this->faker->randomFloat(2, 500, 5000);
        $slotsTotal = $this->faker->numberBetween(1, 10);
        $slotPrice = ceil($totalPrice / $slotsTotal);

        return [
            'coach_id' => User::factory()->coach(),
            'sport_id' => Sport::factory()->create(['is_active' => true]),
            'city_id' => City::factory(),
            'title' => $this->faker->optional()->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'location_name' => $this->faker->streetAddress(),
            'address' => $this->faker->address(),
            'lat' => $this->faker->latitude(55.5, 56.0), // Moscow area
            'lng' => $this->faker->longitude(37.3, 38.0), // Moscow area
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90, 120]),
            'total_price' => $totalPrice,
            'slot_price' => $slotPrice,
            'slots_total' => $slotsTotal,
            'slots_booked' => 0,
            'status' => 'draft',
            'published_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the workout is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the workout is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Indicate that the workout is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'starts_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
