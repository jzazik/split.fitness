<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sports = [
            [
                'slug' => 'running',
                'name' => 'Бег',
                'icon' => null,
                'is_active' => true,
            ],
            [
                'slug' => 'functional',
                'name' => 'Функциональный тренинг',
                'icon' => null,
                'is_active' => true,
            ],
            [
                'slug' => 'yoga',
                'name' => 'Йога',
                'icon' => null,
                'is_active' => true,
            ],
            [
                'slug' => 'cycling',
                'name' => 'Велоспорт',
                'icon' => null,
                'is_active' => true,
            ],
            [
                'slug' => 'boxing',
                'name' => 'Бокс',
                'icon' => null,
                'is_active' => true,
            ],
            [
                'slug' => 'crossfit',
                'name' => 'Кроссфит',
                'icon' => null,
                'is_active' => true,
            ],
        ];

        foreach ($sports as $sport) {
            Sport::updateOrCreate(
                ['slug' => $sport['slug']],
                $sport
            );
        }
    }
}
