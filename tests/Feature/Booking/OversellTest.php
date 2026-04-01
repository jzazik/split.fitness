<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingAction;
use App\Actions\Booking\ReserveSlotAction;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OversellTest extends TestCase
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

    public function test_prevents_oversell_with_concurrent_booking_attempts(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 1,
            'slots_booked' => 0,
        ]);

        $athlete1 = User::factory()->athlete()->create();
        $athlete2 = User::factory()->athlete()->create();

        $exceptions = [];
        $successCount = 0;

        // Simulate two concurrent booking attempts
        // In a real concurrent scenario, both would try to book the last slot simultaneously
        // The lockForUpdate should prevent oversell

        try {
            DB::beginTransaction();
            $this->createBookingAction->execute($workout->fresh(), $athlete1, 1);
            DB::commit();
            $successCount++;
        } catch (ValidationException $e) {
            DB::rollBack();
            $exceptions[] = $e;
        }

        try {
            DB::beginTransaction();
            $this->createBookingAction->execute($workout->fresh(), $athlete2, 1);
            DB::commit();
            $successCount++;
        } catch (ValidationException $e) {
            DB::rollBack();
            $exceptions[] = $e;
        }

        // Exactly one should succeed
        $this->assertEquals(1, $successCount);
        $this->assertCount(1, $exceptions);

        // Verify final state
        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked);
        $this->assertEquals(1, $workout->bookings()->count());
    }

    public function test_transaction_isolation_prevents_race_condition(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 3,
            'slots_booked' => 2, // Only 1 slot available
        ]);

        $athlete1 = User::factory()->athlete()->create();
        $athlete2 = User::factory()->athlete()->create();
        $athlete3 = User::factory()->athlete()->create();

        $results = [];

        // Attempt 3 bookings when only 1 slot is available
        foreach ([$athlete1, $athlete2, $athlete3] as $athlete) {
            try {
                $booking = $this->createBookingAction->execute($workout->fresh(), $athlete, 1);
                $results[] = ['success' => true, 'booking' => $booking];
            } catch (ValidationException $e) {
                $results[] = ['success' => false, 'error' => $e];
            }
        }

        // Exactly one should succeed
        $successfulBookings = array_filter($results, fn ($r) => $r['success']);
        $failedBookings = array_filter($results, fn ($r) => ! $r['success']);

        $this->assertCount(1, $successfulBookings);
        $this->assertCount(2, $failedBookings);

        // Verify workout state
        $workout->refresh();
        $this->assertEquals(3, $workout->slots_booked); // All slots now booked
        $this->assertEquals(1, $workout->bookings()->count()); // Only 1 new booking created
    }

    public function test_lockForUpdate_prevents_dirty_reads(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 4, // Only 1 slot left
        ]);

        $athlete = User::factory()->athlete()->create();

        // First, verify we can book the last slot
        $booking = $this->createBookingAction->execute($workout, $athlete, 1);

        $this->assertNotNull($booking);
        $workout->refresh();
        $this->assertEquals(5, $workout->slots_booked);

        // Now try to book again - should fail
        $athlete2 = User::factory()->athlete()->create();

        $this->expectException(ValidationException::class);
        $this->createBookingAction->execute($workout->fresh(), $athlete2, 1);
    }

    public function test_logs_error_when_no_slots_available(): void
    {
        // This test verifies that when booking fails due to no slots,
        // it is logged as an error with proper context

        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            return $message === 'Slot reservation failed: no available slots'
                && isset($context['workout_id'])
                && isset($context['slots_requested'])
                && isset($context['slots_booked'])
                && isset($context['slots_total'])
                && isset($context['slots_available']);
        });

        $workout = Workout::factory()->published()->create([
            'slots_total' => 1,
            'slots_booked' => 1,
        ]);

        $athlete = User::factory()->athlete()->create();

        try {
            $this->createBookingAction->execute($workout, $athlete, 1);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            // Expected
            $this->assertTrue(true);
        }
    }

    public function test_concurrent_bookings_on_different_workouts_dont_conflict(): void
    {
        // Create first workout
        $workout1 = Workout::factory()->published()->create([
            'slots_total' => 1,
            'slots_booked' => 0,
        ]);

        // Create second workout using the same city and sport to avoid unique constraint issues
        $workout2 = Workout::factory()->published()->create([
            'city_id' => $workout1->city_id,
            'sport_id' => $workout1->sport_id,
            'slots_total' => 1,
            'slots_booked' => 0,
        ]);

        $athlete1 = User::factory()->athlete()->create();
        $athlete2 = User::factory()->athlete()->create();

        // Both should succeed since they're booking different workouts
        $booking1 = $this->createBookingAction->execute($workout1, $athlete1, 1);
        $booking2 = $this->createBookingAction->execute($workout2, $athlete2, 1);

        $this->assertNotNull($booking1);
        $this->assertNotNull($booking2);

        $workout1->refresh();
        $workout2->refresh();

        $this->assertEquals(1, $workout1->slots_booked);
        $this->assertEquals(1, $workout2->slots_booked);
    }

    public function test_multiple_slots_booking_respects_available_capacity(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 7, // 3 slots available
        ]);

        $athlete1 = User::factory()->athlete()->create();
        $athlete2 = User::factory()->athlete()->create();

        // First athlete books 2 slots - should succeed
        $booking1 = $this->createBookingAction->execute($workout->fresh(), $athlete1, 2);
        $this->assertNotNull($booking1);

        $workout->refresh();
        $this->assertEquals(9, $workout->slots_booked);

        // Second athlete tries to book 2 slots - should fail (only 1 left)
        $this->expectException(ValidationException::class);
        $this->createBookingAction->execute($workout->fresh(), $athlete2, 2);

        $workout->refresh();
        $this->assertEquals(9, $workout->slots_booked); // Unchanged
    }

    public function test_transaction_rollback_on_booking_failure_doesnt_increment_slots(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 5, // Full
        ]);

        $athlete = User::factory()->athlete()->create();

        $slotsBefore = $workout->slots_booked;

        try {
            $this->createBookingAction->execute($workout, $athlete, 1);
        } catch (ValidationException $e) {
            // Expected
        }

        $workout->refresh();
        $this->assertEquals($slotsBefore, $workout->slots_booked);
        $this->assertEquals(0, $workout->bookings()->count());
    }
}
