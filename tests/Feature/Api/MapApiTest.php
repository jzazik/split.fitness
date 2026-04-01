<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\CoachProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary related models
        $this->city = City::factory()->create();
        $this->sport = Sport::factory()->create();
        $this->coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create([
            'user_id' => $this->coach->id,
            'rating_avg' => 4.5,
        ]);
    }

    public function test_can_get_workouts_without_filters(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/workouts/map');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'lat',
                        'lng',
                        'sport_name',
                        'location_name',
                        'address',
                        'city_name',
                        'starts_at',
                        'duration_minutes',
                        'slot_price',
                        'slots_total',
                        'slots_booked',
                        'coach_name',
                        'coach_avatar_url',
                        'coach_rating',
                    ],
                ],
            ]);
    }

    public function test_can_filter_by_city_id(): void
    {
        $city1 = City::factory()->create(['slug' => 'city-1']);
        $city2 = City::factory()->create(['slug' => 'city-2']);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $city1->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $city2->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/workouts/map?city_id={$city1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_sport_id(): void
    {
        $sport1 = Sport::factory()->create();
        $sport2 = Sport::factory()->create();

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $sport1->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $sport2->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/workouts/map?sport_id={$sport1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_multiple_sport_ids(): void
    {
        $sport1 = Sport::factory()->create();
        $sport2 = Sport::factory()->create();
        $sport3 = Sport::factory()->create();

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $sport1->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $sport2->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $sport3->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/api/workouts/map?sport_id[]={$sport1->id}&sport_id[]={$sport2->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_date_from(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDays(1),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDays(5),
        ]);

        $dateFrom = now()->addDays(3)->format('Y-m-d');
        $response = $this->getJson("/api/workouts/map?date_from={$dateFrom}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_date_to(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDays(1),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDays(5),
        ]);

        $dateTo = now()->addDays(3)->format('Y-m-d');
        $response = $this->getJson("/api/workouts/map?date_to={$dateTo}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_date_range(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDays(1),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDays(3),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDays(10),
        ]);

        $dateFrom = now()->addDays(2)->format('Y-m-d');
        $dateTo = now()->addDays(7)->format('Y-m-d');
        $response = $this->getJson("/api/workouts/map?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_bbox(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
            'lat' => 55.7558,
            'lng' => 37.6173,
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->addDay(),
            'lat' => 59.9343,
            'lng' => 30.3351,
        ]);

        $response = $this->getJson('/api/workouts/map?ne_lat=56&ne_lng=38&sw_lat=55&sw_lng=37');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_does_not_return_draft_workouts(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/workouts/map');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_does_not_return_cancelled_workouts(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'cancelled',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/workouts/map');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_does_not_return_past_workouts(): void
    {
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'starts_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/workouts/map');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_respects_result_limit(): void
    {
        // Create 201 workouts to test that the limit of 200 is enforced
        // Use a simpler approach to avoid factory unique constraint issues
        $startsAt = now()->addDay();

        for ($i = 0; $i < 201; $i++) {
            Workout::create([
                'coach_id' => $this->coach->id,
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'title' => "Workout {$i}",
                'location_name' => 'Test Location',
                'address' => 'Test Address',
                'lat' => 55.0 + ($i * 0.001),
                'lng' => 37.0 + ($i * 0.001),
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000.00,
                'slot_price' => 100.00,
                'slots_total' => 10,
                'slots_booked' => 0,
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        $response = $this->getJson('/api/workouts/map');

        $response->assertStatus(200);

        $this->assertEquals(200, count($response->json('data')));
    }
}
