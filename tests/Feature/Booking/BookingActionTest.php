<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingAction;
use App\Actions\Booking\ReserveSlotAction;
use App\Models\Booking;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingActionTest extends TestCase
{
    use RefreshDatabase;

    protected ReserveSlotAction $reserveSlotAction;
    protected CreateBookingAction $createBookingAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reserveSlotAction = new ReserveSlotAction();
        $this->createBookingAction = new CreateBookingAction($this->reserveSlotAction);
    }

    public function test_reserve_slot_successfully_increments_slots_booked(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 2,
        ]);

        $this->reserveSlotAction->execute($workout, 1);

        $workout->refresh();
        $this->assertEquals(3, $workout->slots_booked);
    }

    public function test_reserve_slot_fails_when_no_slots_available(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 5,
        ]);

        $this->expectException(ValidationException::class);
        $this->reserveSlotAction->execute($workout, 1);

        $workout->refresh();
        $this->assertEquals(5, $workout->slots_booked);
    }

    public function test_reserve_slot_fails_when_requesting_more_than_available(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 3,
        ]);

        $this->expectException(ValidationException::class);
        $this->reserveSlotAction->execute($workout, 3);

        $workout->refresh();
        $this->assertEquals(3, $workout->slots_booked);
    }

    public function test_create_booking_creates_booking_and_reserves_slot(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 0,
            'slot_price' => 1000,
        ]);
        $athlete = User::factory()->athlete()->create();

        $booking = $this->createBookingAction->execute($workout, $athlete, 1);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($workout->id, $booking->workout_id);
        $this->assertEquals($athlete->id, $booking->athlete_id);
        $this->assertEquals(1, $booking->slots_count);
        $this->assertEquals(1000, $booking->slot_price);
        $this->assertEquals(1000, $booking->total_amount);
        $this->assertEquals('pending_payment', $booking->status);
        $this->assertEquals('pending', $booking->payment_status);
        $this->assertNotNull($booking->booked_at);

        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);
    }

    public function test_create_booking_calculates_total_amount_correctly(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 0,
            'slot_price' => 500,
        ]);
        $athlete = User::factory()->athlete()->create();

        $booking = $this->createBookingAction->execute($workout, $athlete, 3);

        $this->assertEquals(3, $booking->slots_count);
        $this->assertEquals(500, $booking->slot_price);
        $this->assertEquals(1500, $booking->total_amount);

        $workout->refresh();
        $this->assertEquals(3, $workout->slots_booked);
    }

    public function test_create_booking_fails_when_no_slots_available(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 1,
            'slots_booked' => 1,
        ]);
        $athlete = User::factory()->athlete()->create();

        $this->expectException(ValidationException::class);
        $this->createBookingAction->execute($workout, $athlete, 1);

        $this->assertEquals(0, Booking::count());

        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);
    }

    public function test_reserve_slot_uses_transaction_for_atomic_updates(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 1,
            'slots_booked' => 0,
        ]);

        $this->reserveSlotAction->execute($workout, 1);

        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);

        try {
            $this->reserveSlotAction->execute($workout, 1);
        } catch (ValidationException $e) {
            // Expected
        }

        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);
    }
}
