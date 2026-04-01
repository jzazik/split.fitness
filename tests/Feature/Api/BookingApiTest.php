<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\City;
use App\Models\Sport;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $athlete;

    protected User $coach;

    protected City $city;

    protected Sport $sport;

    protected Workout $workout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->athlete = User::factory()->athlete()->create();
        $this->coach = User::factory()->coach()->create();

        $this->city = City::factory()->create();
        $this->sport = Sport::factory()->create();

        $this->workout = Workout::factory()->published()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'starts_at' => now()->addDay(),
            'slots_total' => 5,
            'slots_booked' => 0,
            'slot_price' => 1000,
        ]);
    }

    public function test_athlete_can_create_booking(): void
    {
        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $this->workout->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'booking' => [
                    'id',
                    'workout_id',
                    'slots_count',
                    'total_amount',
                    'status',
                    'payment_status',
                    'booked_at',
                ],
                'payment_url',
            ]);

        $this->assertDatabaseHas('bookings', [
            'workout_id' => $this->workout->id,
            'athlete_id' => $this->athlete->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);

        $this->workout->refresh();
        $this->assertEquals(1, $this->workout->slots_booked);
    }

    public function test_booking_defaults_to_one_slot(): void
    {
        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $this->workout->id,
            ]);

        $response->assertStatus(201);

        $booking = Booking::first();
        $this->assertEquals(1, $booking->slots_count);
    }

    public function test_booking_can_specify_multiple_slots(): void
    {
        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $this->workout->id,
                'slots_count' => 3,
            ]);

        $response->assertStatus(201);

        $booking = Booking::first();
        $this->assertEquals(3, $booking->slots_count);
        $this->assertEquals(3000, $booking->total_amount);

        $this->workout->refresh();
        $this->assertEquals(3, $this->workout->slots_booked);
    }

    public function test_unauthenticated_user_cannot_create_booking(): void
    {
        $response = $this->postJson('/api/bookings', [
            'workout_id' => $this->workout->id,
        ]);

        $response->assertStatus(401);
        $this->assertEquals(0, Booking::count());
    }

    public function test_non_athlete_cannot_create_booking(): void
    {
        $response = $this->actingAs($this->coach)
            ->postJson('/api/bookings', [
                'workout_id' => $this->workout->id,
            ]);

        $response->assertStatus(403);
        $this->assertEquals(0, Booking::count());
    }

    public function test_cannot_book_non_published_workout(): void
    {
        $draftWorkout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $draftWorkout->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workout_id']);

        $this->assertEquals(0, Booking::count());
    }

    public function test_cannot_book_past_workout(): void
    {
        $pastWorkout = Workout::factory()->published()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'starts_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $pastWorkout->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workout_id']);

        $this->assertEquals(0, Booking::count());
    }

    public function test_cannot_create_duplicate_booking(): void
    {
        // First booking
        $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $this->workout->id,
            ])
            ->assertStatus(201);

        // Attempt duplicate booking
        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $this->workout->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workout_id']);

        $this->assertEquals(1, Booking::count());
    }

    public function test_cannot_book_when_no_slots_available(): void
    {
        $fullWorkout = Workout::factory()->published()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'starts_at' => now()->addDay(),
            'slots_total' => 1,
            'slots_booked' => 1,
        ]);

        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $fullWorkout->id,
            ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Booking::where('workout_id', $fullWorkout->id)->count());
    }

    public function test_cannot_book_more_slots_than_available(): void
    {
        $this->workout->update([
            'slots_total' => 5,
            'slots_booked' => 3,
        ]);

        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $this->workout->id,
                'slots_count' => 3,
            ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Booking::count());
    }

    public function test_booking_requires_workout_id(): void
    {
        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workout_id']);
    }

    public function test_booking_requires_existing_workout(): void
    {
        $response = $this->actingAs($this->athlete)
            ->postJson('/api/bookings', [
                'workout_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['workout_id']);
    }
}
