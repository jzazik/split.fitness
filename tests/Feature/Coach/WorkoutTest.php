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

    /**
     * Generate a valid workout start date (tomorrow+, rounded to 15-minute intervals)
     */
    private function validWorkoutDate(int $daysFromNow = 2, string $time = '09:00'): Carbon
    {
        return Carbon::parse("tomorrow +{$daysFromNow} days {$time}");
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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'title' => 'Утренняя тренировка',
                'description' => 'Интенсивная кардио тренировка',
                'location_name' => 'Парк Горького в центре',
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
            'location_name' => 'Парк Горького в центре',
            'status' => 'draft',
            'slots_total' => 3,
            'slots_booked' => 0,
            'slot_price' => 334, // ceil(1000 / 3) = 334
        ]);
    }

    public function test_workout_creation_calculates_slot_price_correctly(): void
    {
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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
        $startsAt = $this->validWorkoutDate()->format('Y-m-d H:i:s');

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

    public function test_approved_coach_can_publish_draft_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.publish', $workout));

        $response->assertRedirect(route('coach.workouts.index'));
        $response->assertSessionHas('success');

        $workout->refresh();
        $this->assertEquals('published', $workout->status);
        $this->assertNotNull($workout->published_at);
    }

    public function test_pending_coach_cannot_publish_workout(): void
    {
        // Create a pending coach
        $pendingCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        $profile = CoachProfile::factory()->create([
            'user_id' => $pendingCoach->id,
            'moderation_status' => 'pending',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $pendingCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $response = $this
            ->actingAs($pendingCoach)
            ->post(route('coach.workouts.publish', $workout));

        // For web routes, authorization failures redirect instead of returning 403
        $response->assertRedirect();

        $workout->refresh();
        $this->assertEquals('draft', $workout->status);
    }

    public function test_cannot_publish_workout_with_past_date(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->subDay(),
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.publish', $workout));

        $response->assertSessionHasErrors('starts_at');

        $workout->refresh();
        $this->assertEquals('draft', $workout->status);
    }

    public function test_coach_cannot_publish_another_coach_workout(): void
    {
        $otherCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        CoachProfile::factory()->create([
            'user_id' => $otherCoach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $otherCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.publish', $workout));

        $response->assertForbidden();

        $workout->refresh();
        $this->assertEquals('draft', $workout->status);
    }

    public function test_coach_can_view_edit_workout_form(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->get(route('coach.workouts.edit', $workout));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Coach/Workouts/Edit')
            ->has('workout')
            ->has('cities')
            ->has('sports')
        );
    }

    public function test_coach_can_update_own_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'draft',
            'starts_at' => Carbon::now()->addDays(2),
            'location_name' => 'Old Location',
            'total_price' => 1000,
            'slots_total' => 3,
        ]);

        $startsAt = $this->validWorkoutDate(5)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'location_name' => 'New Location',
                'address' => 'New Address',
                'lat' => 55.731076,
                'lng' => 37.601224,
                'starts_at' => $startsAt,
                'duration_minutes' => 90,
                'total_price' => 1500,
                'slots_total' => 5,
            ]);

        $response->assertRedirect(route('coach.workouts.index'));
        $response->assertSessionHas('success');

        $workout->refresh();
        $this->assertEquals('Updated Title', $workout->title);
        $this->assertEquals('Updated description', $workout->description);
        $this->assertEquals('New Location', $workout->location_name);
        $this->assertEquals('New Address', $workout->address);
        $this->assertEquals(90, $workout->duration_minutes);
        $this->assertEquals(1500, $workout->total_price);
        $this->assertEquals(5, $workout->slots_total);
        $this->assertEquals(300, $workout->slot_price); // ceil(1500 / 5) = 300
    }

    public function test_coach_cannot_update_another_coach_workout(): void
    {
        $otherCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        CoachProfile::factory()->create([
            'user_id' => $otherCoach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $otherCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'location_name' => 'Original Location',
        ]);

        $startsAt = $this->validWorkoutDate(5)->format('Y-m-d H:i:s');

        $response = $this
            ->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Hacked Location',
                'lat' => 55.731076,
                'lng' => 37.601224,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3,
            ]);

        $response->assertForbidden();

        $workout->refresh();
        $this->assertEquals('Original Location', $workout->location_name);
    }

    public function test_updating_workout_recalculates_slot_price(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'total_price' => 1000,
            'slots_total' => 3,
            'slot_price' => 334,
        ]);

        $startsAt = $this->validWorkoutDate(5)->format('Y-m-d H:i:s');

        $this
            ->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location Name',
                'lat' => 55.731076,
                'lng' => 37.601224,
                'starts_at' => $startsAt,
                'duration_minutes' => 60,
                'total_price' => 700,
                'slots_total' => 4,
            ]);

        $workout->refresh();
        $this->assertEquals(175, $workout->slot_price); // ceil(700 / 4) = 175
    }

    public function test_coach_can_cancel_workout_without_bookings(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'published_at' => Carbon::now(),
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.cancel', $workout));

        $response->assertRedirect(route('coach.workouts.index'));
        $response->assertSessionHas('success');

        $workout->refresh();
        $this->assertEquals('cancelled', $workout->status);
        $this->assertNotNull($workout->cancelled_at);
    }

    public function test_coach_cannot_cancel_another_coach_workout(): void
    {
        $otherCoach = User::factory()->coach()->create([
            'city_id' => $this->city->id,
        ]);

        CoachProfile::factory()->create([
            'user_id' => $otherCoach->id,
            'moderation_status' => 'approved',
        ]);

        $workout = Workout::factory()->create([
            'coach_id' => $otherCoach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'published_at' => Carbon::now(),
        ]);

        $response = $this
            ->actingAs($this->coach)
            ->post(route('coach.workouts.cancel', $workout));

        $response->assertForbidden();

        $workout->refresh();
        $this->assertEquals('published', $workout->status);
    }

    public function test_cannot_republish_already_published_workout(): void
    {
        $originalPublishedAt = now()->subHour();
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'published_at' => $originalPublishedAt,
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->coach)
            ->from(route('coach.workouts.index'))
            ->post(route('coach.workouts.publish', $workout));

        $workout->refresh();

        // Workout should still be published with original timestamp (not updated)
        $this->assertEquals('published', $workout->status);
        $this->assertEquals($originalPublishedAt->timestamp, $workout->published_at->timestamp);

        // Should redirect back with error
        $response->assertRedirect();
        $response->assertSessionHasErrors(['status']);
    }

    public function test_cannot_recancel_already_cancelled_workout(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'cancelled',
            'cancelled_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->coach)
            ->post(route('coach.workouts.cancel', $workout));

        $response->assertForbidden();
    }

    public function test_cannot_reduce_slots_total_below_slots_booked(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'slots_total' => 10,
            'slots_booked' => 5,
        ]);

        $response = $this->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 3, // Less than slots_booked (5)
            ]);

        $response->assertSessionHasErrors(['slots_total']);
    }

    public function test_cannot_create_workout_with_far_future_date(): void
    {
        $response = $this->actingAs($this->coach)
            ->post(route('coach.workouts.store'), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Test Location',
                'lat' => 55.7558,
                'lng' => 37.6173,
                'starts_at' => now()->addYears(2)->format('Y-m-d\TH:i'),
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 10,
            ]);

        $response->assertSessionHasErrors(['starts_at']);
    }

    public function test_cannot_change_core_fields_of_workout_with_bookings(): void
    {
        $workout = Workout::factory()->create([
            'coach_id' => $this->coach->id,
            'sport_id' => $this->sport->id,
            'city_id' => $this->city->id,
            'status' => 'published',
            'slots_booked' => 2,
            'slots_total' => 5,
            'lat' => 55.7558,
            'lng' => 37.6173,
            'starts_at' => now()->addDays(2)->setTime(14, 0), // 14:00 - multiple of 15
            'duration_minutes' => 60,
            'total_price' => 1000,
            'slot_price' => 200, // ceil(1000 / 5)
        ]);

        // Attempt to change location (core field)
        $response = $this->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => 'Updated Location Name',
                'lat' => 55.7600, // Changed
                'lng' => 37.6200, // Changed
                'starts_at' => $workout->starts_at->format('Y-m-d\TH:i'),
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 5,
            ]);

        $response->assertSessionHasErrors(['slots_booked']);

        // Attempt to change time (core field)
        $response = $this->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => $workout->location_name,
                'lat' => $workout->lat,
                'lng' => $workout->lng,
                'starts_at' => now()->addDays(3)->setTime(15, 0)->format('Y-m-d\TH:i'), // Changed
                'duration_minutes' => 60,
                'total_price' => 1000,
                'slots_total' => 5,
            ]);

        $response->assertSessionHasErrors(['slots_booked']);

        // Attempt to change price (core field)
        $response = $this->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'location_name' => $workout->location_name,
                'lat' => $workout->lat,
                'lng' => $workout->lng,
                'starts_at' => $workout->starts_at->format('Y-m-d\TH:i'),
                'duration_minutes' => 60,
                'total_price' => 1500, // Changed
                'slots_total' => 5,
            ]);

        $response->assertSessionHasErrors(['slots_booked']);

        // Can update non-core fields (title, description)
        $response = $this->actingAs($this->coach)
            ->patch(route('coach.workouts.update', $workout), [
                'sport_id' => $this->sport->id,
                'city_id' => $this->city->id,
                'title' => 'Updated Title',
                'description' => 'Updated Description',
                'location_name' => $workout->location_name,
                'lat' => $workout->lat,
                'lng' => $workout->lng,
                'starts_at' => $workout->starts_at->format('Y-m-d\TH:i'),
                'duration_minutes' => $workout->duration_minutes,
                'total_price' => $workout->total_price,
                'slots_total' => $workout->slots_total,
            ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('coach.workouts.index'));

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);
    }
}
