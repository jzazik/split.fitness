<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCityRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_belongs_to_city(): void
    {
        $city = City::create([
            'name' => 'Test City',
            'slug' => 'test-city',
            'country_code' => 'RU',
            'lat' => 55.7558260,
            'lng' => 37.6173000,
        ]);

        $user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'athlete',
            'city_id' => $city->id,
        ]);

        $this->assertInstanceOf(City::class, $user->city);
        $this->assertEquals('Test City', $user->city->name);
    }

    public function test_user_can_exist_without_city(): void
    {
        $user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'athlete',
            'city_id' => null,
        ]);

        $this->assertNull($user->city);
    }
}
