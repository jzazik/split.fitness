<?php

namespace Database\Factories;

use App\Models\CoachProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoachProfile>
 */
class CoachProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bio' => fake()->paragraph(),
            'experience_years' => fake()->numberBetween(1, 30),
            'rating_avg' => fake()->randomFloat(2, 0, 5),
            'rating_count' => fake()->numberBetween(0, 100),
            'moderation_status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'is_public' => fake()->boolean(),
        ];
    }
}
