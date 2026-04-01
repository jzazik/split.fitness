<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingAction;
use App\Actions\Booking\ReserveSlotAction;
use App\Events\BookingCreated;
use App\Jobs\ExpirePendingBookingJob;
use App\Listeners\NotifyCoachNewBooking;
use App\Models\AthleteProfile;
use App\Models\Booking;
use App\Models\City;
use App\Models\CoachProfile;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected ReserveSlotAction $reserveSlotAction;

    protected CreateBookingAction $createBookingAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reserveSlotAction = new ReserveSlotAction;
        $this->createBookingAction = new CreateBookingAction($this->reserveSlotAction);
    }

    public function test_creates_booking_successfully(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 3,
            'slot_price' => 1500,
        ]);

        $athlete = User::factory()->athlete()->create();

        $booking = $this->createBookingAction->execute($workout, $athlete, 2);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($workout->id, $booking->workout_id);
        $this->assertEquals($athlete->id, $booking->athlete_id);
        $this->assertEquals(2, $booking->slots_count);
        $this->assertEquals(1500, $booking->slot_price);
        $this->assertEquals(3000, $booking->total_amount);
        $this->assertEquals('pending_payment', $booking->status);
        $this->assertEquals('pending', $booking->payment_status);
        $this->assertNotNull($booking->booked_at);

        $workout->refresh();
        $this->assertEquals(5, $workout->slots_booked);
    }

    public function test_booking_increments_workout_slots_booked(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 0,
        ]);

        $athlete = User::factory()->athlete()->create();

        $slotsBefore = $workout->slots_booked;

        $booking = $this->createBookingAction->execute($workout, $athlete, 1);

        $workout->refresh();
        $this->assertEquals($slotsBefore + 1, $workout->slots_booked);
    }

    public function test_booking_calculates_total_amount_correctly(): void
    {
        $workout = Workout::factory()->published()->create([
            'slot_price' => 750,
        ]);

        $athlete = User::factory()->athlete()->create();

        $booking = $this->createBookingAction->execute($workout, $athlete, 4);

        $this->assertEquals(750, $booking->slot_price);
        $this->assertEquals(3000, $booking->total_amount);
    }

    public function test_prevents_duplicate_booking_via_api(): void
    {
        $workout = Workout::factory()->published()->create([
            'starts_at' => now()->addDay(),
        ]);

        $athlete = User::factory()->athlete()->create();

        // First booking - should succeed
        $response1 = $this->actingAs($athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $workout->id,
            ]);

        $response1->assertStatus(201);

        // Second booking attempt - should fail with validation error
        $response2 = $this->actingAs($athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $workout->id,
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['workout_id']);

        $this->assertStringContainsString(
            'уже записаны',
            $response2->json('errors.workout_id.0')
        );

        $this->assertEquals(1, Booking::where('athlete_id', $athlete->id)->count());
    }

    public function test_allows_duplicate_booking_if_previous_was_cancelled(): void
    {
        $workout = Workout::factory()->published()->create([
            'starts_at' => now()->addDay(),
        ]);

        $athlete = User::factory()->athlete()->create();

        // First booking
        Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'status' => 'cancelled',
        ]);

        // Second booking should succeed since first was cancelled
        $response = $this->actingAs($athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $workout->id,
            ]);

        $response->assertStatus(201);
        $this->assertEquals(2, Booking::where('athlete_id', $athlete->id)->count());
    }

    public function test_allows_duplicate_booking_if_previous_expired(): void
    {
        $workout = Workout::factory()->published()->create([
            'starts_at' => now()->addDay(),
        ]);

        $athlete = User::factory()->athlete()->create();

        // First booking - expired
        Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'status' => 'expired',
        ]);

        // Second booking should succeed
        $response = $this->actingAs($athlete)
            ->postJson('/api/bookings', [
                'workout_id' => $workout->id,
            ]);

        $response->assertStatus(201);
        $this->assertEquals(2, Booking::where('athlete_id', $athlete->id)->count());
    }

    public function test_booking_expires_after_ttl(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 2,
        ]);

        $athlete = User::factory()->athlete()->create();

        // Create a booking 20 minutes ago (past the 15-minute TTL)
        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'slots_count' => 1,
            'slot_price' => 1000,
            'total_amount' => 1000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'created_at' => now()->subMinutes(20),
        ]);

        $this->assertEquals('pending_payment', $booking->status);

        // Run the expiration job
        $job = new ExpirePendingBookingJob;
        $job->handle();

        // Booking should now be expired
        $booking->refresh();
        $this->assertEquals('expired', $booking->status);

        // Slot should be released
        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);
    }

    public function test_booking_does_not_expire_before_ttl(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 1,
        ]);

        $athlete = User::factory()->athlete()->create();

        // Create a booking 10 minutes ago (within the 15-minute TTL)
        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'slots_count' => 1,
            'slot_price' => 1000,
            'total_amount' => 1000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'created_at' => now()->subMinutes(10),
        ]);

        // Run the expiration job
        $job = new ExpirePendingBookingJob;
        $job->handle();

        // Booking should still be pending
        $booking->refresh();
        $this->assertEquals('pending_payment', $booking->status);

        // Slot should remain booked
        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);
    }

    public function test_expired_booking_releases_multiple_slots(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 5,
        ]);

        $athlete = User::factory()->athlete()->create();

        // Create a booking with 3 slots, expired
        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'slots_count' => 3,
            'slot_price' => 1000,
            'total_amount' => 3000,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'created_at' => now()->subMinutes(20),
        ]);

        $job = new ExpirePendingBookingJob;
        $job->handle();

        $booking->refresh();
        $this->assertEquals('expired', $booking->status);

        $workout->refresh();
        $this->assertEquals(2, $workout->slots_booked); // 5 - 3 = 2
    }

    public function test_paid_bookings_are_not_expired(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 1,
        ]);

        $athlete = User::factory()->athlete()->create();

        // Create a paid booking from 30 minutes ago
        $paidBooking = Booking::factory()->paid()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'slots_count' => 1,
            'slot_price' => 1000,
            'total_amount' => 1000,
            'created_at' => now()->subMinutes(30),
        ]);

        $job = new ExpirePendingBookingJob;
        $job->handle();

        $paidBooking->refresh();
        $this->assertEquals('paid', $paidBooking->status);

        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);
    }

    public function test_booking_logs_creation_with_context(): void
    {
        Queue::fake();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Slot reserved successfully'
                    && isset($context['workout_id'])
                    && isset($context['slots_count'])
                    && isset($context['slots_booked_before'])
                    && isset($context['slots_booked_after'])
                    && isset($context['slots_total']);
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Booking created'
                    && isset($context['booking_id'])
                    && isset($context['workout_id'])
                    && isset($context['athlete_id'])
                    && isset($context['slots_count'])
                    && isset($context['status'])
                    && $context['status'] === 'pending_payment'
                    && isset($context['total_amount']);
            });

        $workout = Workout::factory()->published()->create();
        $athlete = User::factory()->athlete()->create();

        $this->createBookingAction->execute($workout, $athlete, 1);
    }

    public function test_booking_lifecycle_from_creation_to_expiration(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 0,
            'slot_price' => 2000,
        ]);

        $athlete = User::factory()->athlete()->create();

        // Step 1: Create booking
        $booking = $this->createBookingAction->execute($workout, $athlete, 2);

        $this->assertEquals('pending_payment', $booking->status);
        $this->assertEquals('pending', $booking->payment_status);
        $this->assertEquals(2000, $booking->slot_price);
        $this->assertEquals(4000, $booking->total_amount);

        $workout->refresh();
        $this->assertEquals(2, $workout->slots_booked);

        // Step 2: Simulate time passage (move created_at to 20 minutes ago)
        \DB::table('bookings')
            ->where('id', $booking->id)
            ->update(['created_at' => now()->subMinutes(20)]);

        // Step 3: Run expiration job
        $job = new ExpirePendingBookingJob;
        $job->handle();

        // Step 4: Verify expiration
        $booking->refresh();
        $this->assertEquals('expired', $booking->status);

        $workout->refresh();
        $this->assertEquals(0, $workout->slots_booked);
    }

    public function test_multiple_athletes_can_book_same_workout(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 0,
        ]);

        $athlete1 = User::factory()->athlete()->create();
        $athlete2 = User::factory()->athlete()->create();
        $athlete3 = User::factory()->athlete()->create();

        $booking1 = $this->createBookingAction->execute($workout, $athlete1, 2);
        $booking2 = $this->createBookingAction->execute($workout->fresh(), $athlete2, 1);
        $booking3 = $this->createBookingAction->execute($workout->fresh(), $athlete3, 3);

        $this->assertNotNull($booking1);
        $this->assertNotNull($booking2);
        $this->assertNotNull($booking3);

        $workout->refresh();
        $this->assertEquals(6, $workout->slots_booked);
        $this->assertEquals(3, $workout->bookings()->count());
    }

    public function test_booking_created_event_is_dispatched(): void
    {
        Event::fake([BookingCreated::class]);

        $workout = Workout::factory()->published()->create();
        $athlete = User::factory()->athlete()->create();

        $booking = $this->createBookingAction->execute($workout, $athlete, 1);

        Event::assertDispatched(BookingCreated::class, function ($event) use ($booking) {
            return $event->booking->id === $booking->id;
        });

        Event::assertListening(
            BookingCreated::class,
            NotifyCoachNewBooking::class
        );
    }

    public function test_athlete_can_view_own_booking_details(): void
    {
        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $workout = Workout::factory()->published()->create();
        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
        ]);

        $response = $this->actingAs($athlete)
            ->get(route('athlete.bookings.show', $booking->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Athlete/Bookings/Show')
            ->has('booking')
            ->where('booking.id', $booking->id)
        );
    }

    public function test_athlete_cannot_view_other_athlete_booking(): void
    {
        $athlete1 = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete1->id]);

        $athlete2 = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete2->id]);

        $workout = Workout::factory()->published()->create();

        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete1->id,
        ]);

        $response = $this->actingAs($athlete2)
            ->get(route('athlete.bookings.show', $booking->id));

        $response->assertStatus(403);
    }

    public function test_booking_show_page_loads_all_relationships(): void
    {
        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $workout = Workout::factory()->published()->create();
        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
        ]);

        $response = $this->actingAs($athlete)
            ->get(route('athlete.bookings.show', $booking->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('booking.workout.sport')
            ->has('booking.workout.coach')
            ->has('booking.workout.city')
        );
    }

    public function test_coach_cannot_view_athlete_booking(): void
    {
        $city = City::factory()->create();
        $coach = User::factory()->coach()->create(['city_id' => $city->id]);
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $athlete = User::factory()->athlete()->create();
        AthleteProfile::factory()->create(['user_id' => $athlete->id]);

        $workout = Workout::factory()->published()->create();

        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
        ]);

        $response = $this->actingAs($coach)
            ->get(route('athlete.bookings.show', $booking->id));

        // Coach doesn't have access to athlete routes - will get middleware error
        $response->assertStatus(403);
    }
}
