<?php

namespace Tests\Feature\Booking;

use App\Jobs\ExpirePendingBookingJob;
use App\Models\Booking;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExpirePendingBookingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_pending_bookings_older_than_15_minutes(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 2,
        ]);

        $athlete1 = User::factory()->athlete()->create();
        $athlete2 = User::factory()->athlete()->create();

        // Create an old pending booking (20 minutes ago)
        $oldBooking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete1->id,
            'status' => 'pending_payment',
            'slots_count' => 1,
            'created_at' => now()->subMinutes(20),
        ]);

        // Create a recent pending booking (5 minutes ago) - should NOT expire
        $recentBooking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete2->id,
            'status' => 'pending_payment',
            'slots_count' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        // Run the job
        $job = new ExpirePendingBookingJob;
        $job->handle();

        // Verify old booking is expired
        $oldBooking->refresh();
        $this->assertEquals('expired', $oldBooking->status);

        // Verify recent booking is NOT expired
        $recentBooking->refresh();
        $this->assertEquals('pending_payment', $recentBooking->status);

        // Verify slots_booked was decremented (only for the expired booking)
        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked); // Was 2, decremented by 1
    }

    public function test_releases_multiple_slots_when_booking_expires(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 5,
        ]);

        $athlete = User::factory()->athlete()->create();

        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'status' => 'pending_payment',
            'slots_count' => 3,
            'created_at' => now()->subMinutes(20),
        ]);

        $job = new ExpirePendingBookingJob;
        $job->handle();

        $booking->refresh();
        $this->assertEquals('expired', $booking->status);

        $workout->refresh();
        $this->assertEquals(2, $workout->slots_booked); // Was 5, decremented by 3
    }

    public function test_does_not_expire_paid_bookings(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 1,
        ]);

        $athlete = User::factory()->athlete()->create();

        $paidBooking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'created_at' => now()->subMinutes(20),
        ]);

        $job = new ExpirePendingBookingJob;
        $job->handle();

        $paidBooking->refresh();
        $this->assertEquals('paid', $paidBooking->status);

        $workout->refresh();
        $this->assertEquals(1, $workout->slots_booked); // Unchanged
    }

    public function test_logs_booking_expiration_with_context(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Booking expired and slot released'
                    && isset($context['booking_id'])
                    && isset($context['workout_id'])
                    && isset($context['athlete_id'])
                    && isset($context['slots_count'])
                    && isset($context['status'])
                    && $context['status'] === 'expired'
                    && isset($context['slots_booked_before'])
                    && isset($context['slots_booked_after']);
            });

        $workout = Workout::factory()->published()->create([
            'slots_total' => 5,
            'slots_booked' => 1,
        ]);

        $athlete = User::factory()->athlete()->create();

        $booking = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete->id,
            'status' => 'pending_payment',
            'slots_count' => 1,
            'created_at' => now()->subMinutes(20),
        ]);

        $job = new ExpirePendingBookingJob;
        $job->handle();
    }

    public function test_logs_warning_when_processing_many_bookings(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 2000,
            'slots_booked' => 1500,
        ]);

        // Create 1001 old bookings to trigger warning (different athletes to avoid unique constraint)
        foreach (range(1, 1001) as $i) {
            $athlete = User::factory()->athlete()->create();
            Booking::factory()->create([
                'workout_id' => $workout->id,
                'athlete_id' => $athlete->id,
                'status' => 'pending_payment',
                'slots_count' => 1,
                'created_at' => now()->subMinutes(20),
            ]);
        }

        Log::shouldReceive('info')->times(1001);
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'ExpirePendingBookingJob processed large number of bookings'
                    && isset($context['count'])
                    && $context['count'] > 1000;
            });

        $job = new ExpirePendingBookingJob;
        $job->handle();
    }

    public function test_handles_transaction_isolation_correctly(): void
    {
        $workout = Workout::factory()->published()->create([
            'slots_total' => 10,
            'slots_booked' => 3,
        ]);

        $athlete1 = User::factory()->athlete()->create();
        $athlete2 = User::factory()->athlete()->create();

        // Create two expired bookings for the same workout
        $booking1 = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete1->id,
            'status' => 'pending_payment',
            'slots_count' => 1,
            'created_at' => now()->subMinutes(20),
        ]);

        $booking2 = Booking::factory()->create([
            'workout_id' => $workout->id,
            'athlete_id' => $athlete2->id,
            'status' => 'pending_payment',
            'slots_count' => 2,
            'created_at' => now()->subMinutes(25),
        ]);

        $job = new ExpirePendingBookingJob;
        $job->handle();

        // Both should be expired
        $booking1->refresh();
        $booking2->refresh();
        $this->assertEquals('expired', $booking1->status);
        $this->assertEquals('expired', $booking2->status);

        // Slots should be decremented correctly
        $workout->refresh();
        $this->assertEquals(0, $workout->slots_booked); // Was 3, decremented by 1+2
    }
}
