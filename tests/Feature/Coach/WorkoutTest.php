<?php

namespace Tests\Feature\Coach;

use App\Models\City;
use App\Models\CoachProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private City $city;

    private Sport $sport;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a coach user with completed and approved profile
        $this->city = City::factory()->create();
        $this->sport = Sport::factory()->create(['is_active' => true]);

        $this->coach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        $profile = CoachProfile::factory()->create([
            'user_id' => $this->coach->id,
            'bio' => 'Опытный тренер с многолетним стажем',
            'moderation_status' => 'approved',
        ]);

        $profile->sports()->attach($this->sport->id);
    }

    public function test_coach_can_view_workouts_index(): void
    {
        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Coach/Workouts/Index')
            ->has('workouts')
        );
    }

    public function test_coach_can_view_create_workout_form(): void
    {
        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Coach/Workouts/Create')
            ->has('cities')
            ->has('sports')
        );
    }

    public function test_coach_can_create_draft_workout(): void
    {
        $startsAt = Carbon::now()->addDays(2)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'title' => 'Утренняя тренировка',
                'description' => 'Интенсивная кардио тренировка',
                'location_name' => 'Парк Горького',
                'address' => 'Москва, Парк Горького, главная аллея',
                'lat' => 55.731076,
                'lng' => 37.601224,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertRedirect(route('coach.workouts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('workouts', [
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'location_name' => 'Парк Горького',
            'status' => 'draft',
            'slots_total' => 3,
            'slots_booked' => 0,
            'slot_price' => 334, // ceil(1000 / 3) = 334
        ]);
    }

    public function test_workout_creation_calculates_slot_price_correctly(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 90,
                'total_price' => 500,
                'slots_total' => 4,
            ]);

        $workout = Workout::latest()->first();
        $this->assertEquals(125, $workout->slot_price); // ceil(500 / 4) = 125
    }

    public function test_workout_creation_requires_sport_id(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertSessionHasErrors('sport_id');
    }

    public function test_workout_creation_requires_city_id(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertSessionHasErrors('city_id');
    }

    public function test_workout_creation_requires_location_name(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertSessionHasErrors('location_name');
    }

    public function test_workout_creation_requires_valid_coordinates(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertSessionHasErrors('lat');
    }

    public function test_workout_creation_requires_future_date(): void
    {
        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertSessionHasErrors('starts_at');
    }

    public function test_workout_creation_validates_duration_minutes(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 0,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertSessionHasErrors('duration_minutes');
    }

    public function test_workout_creation_validates_total_price(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 0,
                'slots_total' => 3,
            ]);

        $response->assertSessionHasErrors('total_price');
    }

    public function test_workout_creation_validates_slots_total(): void
    {
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 0,
            ]);

        $response->assertSessionHasErrors('slots_total');
    }

    public function test_non_coach_cannot_create_workout(): void
    {
        $athlete = User::factory()->athlete()->create();
        $startsAt = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($athlete)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertForbidden();
    }

    public function test_workouts_index_shows_only_coach_own_workouts(): void
    {
        $otherCoach = User::factory()->coach()->create();
        CoachProfile::factory()->create([
            'user_id' => $otherCoach->id,
            'moderation_status' => 'approved',
        ]);

        // Create workout for current coach
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

        // Create workout for other coach
        Workout::factory()->create([
            'coach_id' => $otherCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workouts.data', 1) // Only one workout for current coach
        );
    }

    public function test_workouts_index_can_filter_by_status(): void
    {
        // Create workouts with different statuses
        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'published_at' => Carbon::now(),
        ]);

        Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
        ]);

        // Filter by draft
        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index', ['status' => 'draft']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workouts.data', 1)
            ->where('filters.status', 'draft')
        );

        // Filter by published
        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index', ['status' => 'published']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workouts.data', 1)
            ->where('filters.status', 'published')
        );

        // Filter by cancelled
        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index', ['status' => 'cancelled']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workouts.data', 1)
            ->where('filters.status', 'cancelled')
        );

        // No filter - should show all 3
        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workouts.data', 3)
        );
    }

    public function test_workouts_index_orders_by_starts_at_desc(): void
    {
        $futureWorkout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'starts_at' => Carbon::now()->addDays(5),
        ]);

        $nearWorkout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $distantWorkout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'starts_at' => Carbon::now()->addDays(10),
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workouts.data', 3)
            ->where('workouts.data.0.id', $distantWorkout->id) // Most distant first
            ->where('workouts.data.1.id', $futureWorkout->id)
            ->where('workouts.data.2.id', $nearWorkout->id) // Nearest last
        );
    }

    public function test_workouts_index_paginates_results(): void
    {
        // Create 20 workouts
        Workout::factory()->count(20)->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('workouts.data', 15) // 15 per page
            ->where('workouts.total', 20)
            ->has('workouts.links')
        );
    }
}
