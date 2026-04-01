<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_page_loads_successfully(): void
    {
        $response = $this->get('/map');

        $response->assertOk();
    }

    public function test_map_page_returns_inertia_response(): void
    {
        $response = $this->get('/map');

        $response->assertInertia(fn ($page) => $page
            ->component('Public/Map/Index')
        );
    }

    public function test_map_page_includes_cities(): void
    {
        // Create some cities (use unique names to avoid seeder conflicts)
        City::factory()->create(['name' => 'Test City 1', 'slug' => 'test-city-1']);
        City::factory()->create(['name' => 'Test City 2', 'slug' => 'test-city-2']);

        $response = $this->get('/map');

        $response->assertInertia(fn ($page) => $page
            ->has('cities', 2)
            ->has('cities.0', fn ($city) => $city
                ->has('id')
                ->has('name')
                ->has('lat')
                ->has('lng')
            )
        );
    }

    public function test_map_page_includes_active_sports(): void
    {
        // Create active and inactive sports
        Sport::factory()->create(['name' => 'Yoga', 'is_active' => true]);
        Sport::factory()->create(['name' => 'Running', 'is_active' => true]);
        Sport::factory()->create(['name' => 'Inactive Sport', 'is_active' => false]);

        $response = $this->get('/map');

        $response->assertInertia(fn ($page) => $page
            ->has('sports', 2) // Only active sports
            ->has('sports.0', fn ($sport) => $sport
                ->has('id')
                ->has('name')
                ->has('slug')
                ->has('icon')
            )
        );
    }

    public function test_map_page_cities_are_ordered_by_name(): void
    {
        City::factory()->create(['name' => 'Zelenograd', 'slug' => 'zelenograd']);
        City::factory()->create(['name' => 'Apatity', 'slug' => 'apatity']);
        City::factory()->create(['name' => 'Moscow', 'slug' => 'moscow-test']);

        $response = $this->get('/map');

        $response->assertInertia(fn ($page) => $page
            ->has('cities', 3)
            ->where('cities.0.name', 'Apatity')
            ->where('cities.1.name', 'Moscow')
            ->where('cities.2.name', 'Zelenograd')
        );
    }

    public function test_map_page_sports_are_ordered_by_name(): void
    {
        Sport::factory()->create(['name' => 'Yoga', 'is_active' => true]);
        Sport::factory()->create(['name' => 'Boxing', 'is_active' => true]);
        Sport::factory()->create(['name' => 'Running', 'is_active' => true]);

        $response = $this->get('/map');

        $response->assertInertia(fn ($page) => $page
            ->has('sports', 3)
            ->where('sports.0.name', 'Boxing')
            ->where('sports.1.name', 'Running')
            ->where('sports.2.name', 'Yoga')
        );
    }

    public function test_map_route_is_publicly_accessible(): void
    {
        // Map should be accessible without authentication
        $response = $this->get(route('map'));

        $response->assertOk();
    }
}
