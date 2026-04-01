<?php

namespace Database\Factories;

use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportFactory extends Factory
{
    protected $model = Sport::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();
        $slug = strtolower($name) . '-' . $this->faker->unique()->numberBetween(1, 10000);

        return [
            'slug' => $slug,
            'name' => ucfirst($name),
            'icon' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
