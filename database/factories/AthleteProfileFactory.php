<?php

namespace Database\Factories;

use App\Models\AthleteProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteProfile>
 */
class AthleteProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'emergency_contact' => fake()->name().' '.fake()->phoneNumber(),
        ];
    }
}
