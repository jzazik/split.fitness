<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            [
                'name' => 'Москва',
                'slug' => 'moscow',
                'country_code' => 'RU',
                'lat' => 55.7558260,
                'lng' => 37.6173000,
            ],
            [
                'name' => 'Санкт-Петербург',
                'slug' => 'saint-petersburg',
                'country_code' => 'RU',
                'lat' => 59.9310584,
                'lng' => 30.3609096,
            ],
            [
                'name' => 'Екатеринбург',
                'slug' => 'ekaterinburg',
                'country_code' => 'RU',
                'lat' => 56.8389261,
                'lng' => 60.6057025,
            ],
            [
                'name' => 'Казань',
                'slug' => 'kazan',
                'country_code' => 'RU',
                'lat' => 55.8304307,
                'lng' => 49.0660806,
            ],
            [
                'name' => 'Новосибирск',
                'slug' => 'novosibirsk',
                'country_code' => 'RU',
                'lat' => 55.0083526,
                'lng' => 82.9357327,
            ],
        ];

        foreach ($cities as $city) {
            City::updateOrCreate(
                ['slug' => $city['slug']],
                $city
            );
        }
    }
}
