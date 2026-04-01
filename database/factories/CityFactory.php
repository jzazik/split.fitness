<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        $cities = [
            ['name' => 'Москва', 'slug' => 'moscow', 'lat' => 55.7558, 'lng' => 37.6173],
            ['name' => 'Санкт-Петербург', 'slug' => 'saint-petersburg', 'lat' => 59.9311, 'lng' => 30.3609],
            ['name' => 'Казань', 'slug' => 'kazan', 'lat' => 55.7887, 'lng' => 49.1221],
            ['name' => 'Екатеринбург', 'slug' => 'yekaterinburg', 'lat' => 56.8389, 'lng' => 60.6057],
            ['name' => 'Новосибирск', 'slug' => 'novosibirsk', 'lat' => 55.0084, 'lng' => 82.9357],
        ];

        $city = $this->faker->randomElement($cities);

        return [
            'name' => $city['name'],
            'slug' => $city['slug'],
            'country_code' => 'RU',
            'lat' => $city['lat'],
            'lng' => $city['lng'],
        ];
    }
}
