<?php

namespace Tests\Unit\Models;

use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase;

    public function test_city_has_fillable_attributes(): void
    {
        $city = new City;
        $fillable = $city->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('country_code', $fillable);
        $this->assertContains('lat', $fillable);
        $this->assertContains('lng', $fillable);
    }

    public function test_city_can_be_created(): void
    {
        $city = City::create([
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_code' => 'RU',
            'lat' => 55.7558260,
            'lng' => 37.6173000,
        ]);

        $this->assertDatabaseHas('cities', [
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_code' => 'RU',
        ]);
    }

    public function test_city_lat_lng_are_cast_to_decimal(): void
    {
        $city = City::create([
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_code' => 'RU',
            'lat' => 55.7558260,
            'lng' => 37.6173000,
        ]);

        $this->assertIsFloat($city->lat);
        $this->assertIsFloat($city->lng);
    }
}
