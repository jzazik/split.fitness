<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sports = [
            ['slug' => 'running', 'name' => 'Бег'],
            ['slug' => 'cycling', 'name' => 'Велоспорт'],
            ['slug' => 'yoga', 'name' => 'Йога на открытом воздухе'],
            ['slug' => 'functional', 'name' => 'Функциональный тренинг'],
            ['slug' => 'crossfit', 'name' => 'Кроссфит на улице'],
            ['slug' => 'nordic-walking', 'name' => 'Скандинавская ходьба'],
            ['slug' => 'hiking', 'name' => 'Хайкинг'],
            ['slug' => 'trail-running', 'name' => 'Трейлраннинг'],
            ['slug' => 'tennis', 'name' => 'Теннис'],
            ['slug' => 'basketball', 'name' => 'Баскетбол'],
            ['slug' => 'volleyball', 'name' => 'Волейбол'],
            ['slug' => 'football', 'name' => 'Футбол'],
            ['slug' => 'skateboarding', 'name' => 'Скейтбординг'],
            ['slug' => 'rollerblading', 'name' => 'Роликовые коньки'],
            ['slug' => 'outdoor-swimming', 'name' => 'Плавание на открытой воде'],
            ['slug' => 'sup', 'name' => 'SUP-сёрфинг'],
            ['slug' => 'kayaking', 'name' => 'Каякинг'],
            ['slug' => 'stretching', 'name' => 'Растяжка на улице'],
            ['slug' => 'bootcamp', 'name' => 'Буткемп'],
            ['slug' => 'skiing', 'name' => 'Лыжи'],
            ['slug' => 'ice-skating', 'name' => 'Коньки'],
        ];

        $canonicalSlugs = array_column($sports, 'slug');

        foreach ($sports as $sport) {
            Sport::updateOrCreate(
                ['slug' => $sport['slug']],
                ['name' => $sport['name'], 'is_active' => true],
            );
        }

        Sport::whereNotIn('slug', $canonicalSlugs)->update(['is_active' => false]);
    }
}
