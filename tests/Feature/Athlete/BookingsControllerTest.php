<?php

namespace Tests\Feature\Athlete;

use App\Models\AthleteProfile;
use App\Models\Booking;
use App\Models\City;
use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createAthleteWithProfile(): User
    {
        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        return $athlete;
    }

    public function test_athlete_can_view_bookings_index(): void
    {
        $athlete = $this->createAthleteWithProfile();

        $response = $this->actingAs($athlete)
            ->get(route('athlete.bookings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Athlete/Bookings/Index')
            ->has('upcoming')
            ->has('past')
            ->has('cancelled')
        );
    }

    public function test_bookings_are_separated_into_upcoming_past_and_cancelled(): void
    {
        $athlete = $this->createAthleteWithProfile();
        $city = City::factory()->create();

        $futureWorkout = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->addDays(3),
            'city_id' => $city->id,
        ]);
        $upcomingBooking = Booking::factory()->create([
            'athlete_id' => $athlete->id,
            'workout_id' => $futureWorkout->id,
            'status' => 'paid',
        ]);

        $pastWorkout = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->subDays(2),
            'city_id' => $city->id,
        ]);
        $pastBooking = Booking::factory()->create([
            'athlete_id' => $athlete->id,
            'workout_id' => $pastWorkout->id,
            'status' => 'paid',
        ]);

        $cancelledWorkout = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->addDays(5),
            'city_id' => $city->id,
        ]);
        $cancelledBooking = Booking::factory()->create([
            'athlete_id' => $athlete->id,
            'workout_id' => $cancelledWorkout->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($athlete)
            ->get(route('athlete.bookings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Athlete/Bookings/Index')
            ->where('upcoming.0.id', $upcomingBooking->id)
            ->where('past.0.id', $pastBooking->id)
            ->where('cancelled.0.id', $cancelledBooking->id)
        );
    }

    public function test_pending_payment_bookings_appear_in_upcoming(): void
    {
        $athlete = $this->createAthleteWithProfile();

        $workout = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->addHours(3),
        ]);
        $booking = Booking::factory()->create([
            'athlete_id' => $athlete->id,
            'workout_id' => $workout->id,
            'status' => 'pending_payment',
        ]);

        $response = $this->actingAs($athlete)
            ->get(route('athlete.bookings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('upcoming.0.id', $booking->id)
            ->where('past', [])
            ->where('cancelled', [])
        );
    }

    public function test_expired_bookings_appear_in_cancelled(): void
    {
        $athlete = $this->createAthleteWithProfile();

        $workout = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->addDays(2),
        ]);
        $booking = Booking::factory()->create([
            'athlete_id' => $athlete->id,
            'workout_id' => $workout->id,
            'status' => 'expired',
        ]);

        $response = $this->actingAs($athlete)
            ->get(route('athlete.bookings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('upcoming', [])
            ->where('past', [])
            ->where('cancelled.0.id', $booking->id)
        );
    }

    public function test_bookings_are_eager_loaded_with_relationships(): void
    {
        $athlete = $this->createAthleteWithProfile();

        $workout = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->addDays(1),
        ]);
        $booking = Booking::factory()->create([
            'athlete_id' => $athlete->id,
            'workout_id' => $workout->id,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($athlete)
            ->get(route('athlete.bookings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('upcoming.0.workout.sport.name', $workout->sport->name)
            ->where('upcoming.0.workout.coach.id', $workout->coach_id)
            ->where('upcoming.0.workout.city.name', $workout->city->name)
        );
    }

    public function test_only_own_bookings_are_visible(): void
    {
        $athlete1 = $this->createAthleteWithProfile();
        $athlete2 = $this->createAthleteWithProfile();
        $city = City::factory()->create();

        $workout1 = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->addDays(1),
            'city_id' => $city->id,
        ]);
        $booking1 = Booking::factory()->create([
            'athlete_id' => $athlete1->id,
            'workout_id' => $workout1->id,
            'status' => 'paid',
        ]);

        $workout2 = Workout::factory()->published()->create([
            'starts_at' => Carbon::now()->addDays(2),
            'city_id' => $city->id,
        ]);
        $booking2 = Booking::factory()->create([
            'athlete_id' => $athlete2->id,
            'workout_id' => $workout2->id,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($athlete1)
            ->get(route('athlete.bookings'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('upcoming.0.id', $booking1->id)
            ->where('upcoming', fn ($upcoming) => count($upcoming) === 1)
        );
    }

    public function test_guest_cannot_access_bookings(): void
    {
        $response = $this->get(route('athlete.bookings'));

        $response->assertRedirect(route('login'));
    }

    public function test_coach_cannot_access_athlete_bookings(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)
            ->get(route('athlete.bookings'));

        $response->assertForbidden();
    }
}
