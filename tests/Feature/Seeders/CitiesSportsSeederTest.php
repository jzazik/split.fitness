<?php

namespace Tests\Feature\Seeders;

use App\Models\City;
use App\Models\Sport;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\SportsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitiesSportsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cities_seeder_creates_five_cities(): void
    {
        $this->seed(CitiesSeeder::class);

        $this->assertEquals(5, City::count());
    }

    public function test_cities_seeder_creates_required_cities(): void
    {
        $this->seed(CitiesSeeder::class);

        $this->assertDatabaseHas('cities', ['slug' => 'moscow', 'name' => 'Москва']);
        $this->assertDatabaseHas('cities', ['slug' => 'saint-petersburg', 'name' => 'Санкт-Петербург']);
        $this->assertDatabaseHas('cities', ['slug' => 'ekaterinburg', 'name' => 'Екатеринбург']);
        $this->assertDatabaseHas('cities', ['slug' => 'kazan', 'name' => 'Казань']);
        $this->assertDatabaseHas('cities', ['slug' => 'novosibirsk', 'name' => 'Новосибирск']);
    }

    public function test_cities_have_coordinates(): void
    {
        $this->seed(CitiesSeeder::class);

        $moscow = City::where('slug', 'moscow')->first();
        $this->assertNotNull($moscow->lat);
        $this->assertNotNull($moscow->lng);
    }

    public function test_sports_seeder_creates_six_sports(): void
    {
        $this->seed(SportsSeeder::class);

        $this->assertEquals(6, Sport::count());
    }

    public function test_sports_seeder_creates_required_sports(): void
    {
        $this->seed(SportsSeeder::class);

        $this->assertDatabaseHas('sports', ['slug' => 'running', 'name' => 'Бег']);
        $this->assertDatabaseHas('sports', ['slug' => 'functional', 'name' => 'Функциональный тренинг']);
        $this->assertDatabaseHas('sports', ['slug' => 'yoga', 'name' => 'Йога']);
        $this->assertDatabaseHas('sports', ['slug' => 'cycling', 'name' => 'Велоспорт']);
        $this->assertDatabaseHas('sports', ['slug' => 'boxing', 'name' => 'Бокс']);
        $this->assertDatabaseHas('sports', ['slug' => 'crossfit', 'name' => 'Кроссфит']);
    }

    public function test_sports_are_active_by_default(): void
    {
        $this->seed(SportsSeeder::class);

        $allActive = Sport::where('is_active', false)->count();
        $this->assertEquals(0, $allActive);
    }
}
