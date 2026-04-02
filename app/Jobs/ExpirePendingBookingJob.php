<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpirePendingBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $expiredBookings = Booking::query()
            ->where('status', 'pending_payment')
            ->where('created_at', '<', now()->subMinutes(15))
            ->with('workout')
            ->get();

        $processedCount = 0;

        foreach ($expiredBookings as $booking) {
            try {
                DB::transaction(function () use ($booking) {
                    // Re-lock and re-check booking status to prevent race conditions
                    $booking = Booking::lockForUpdate()->find($booking->id);

                    if (! $booking || $booking->status !== 'pending_payment') {
                        return;
                    }

                    // Lock the workout to prevent race conditions
                    $workout = $booking->workout()->lockForUpdate()->first();

                    if (! $workout) {
                        Log::warning('Workout not found during booking expiration, cancelling booking', [
                            'booking_id' => $booking->id,
                            'workout_id' => $booking->workout_id,
                        ]);

                        $booking->update(['status' => 'cancelled']);

                        return;
                    }

                    $slotsBefore = $workout->slots_booked;

                    // Update booking status
                    $booking->update(['status' => 'expired']);

                    // Release the slot(s) - abort transaction if data corruption detected
                    if ($workout->slots_booked < $booking->slots_count) {
                        Log::critical('Slot count data corruption detected during booking expiration - aborting transaction', [
                            'booking_id' => $booking->id,
                            'workout_id' => $workout->id,
                            'slots_booked' => $workout->slots_booked,
                            'booking_slots_count' => $booking->slots_count,
                        ]);

                        throw new \RuntimeException('Data corruption detected: slots_booked < booking->slots_count');
                    }

                    $workout->decrement('slots_booked', $booking->slots_count);

                    $slotsAfter = $workout->fresh()->slots_booked;

                    Log::info('Booking expired and slot released', [
                        'booking_id' => $booking->id,
                        'workout_id' => $workout->id,
                        'athlete_id' => $booking->athlete_id,
                        'slots_count' => $booking->slots_count,
                        'status' => 'expired',
                        'slots_booked_before' => $slotsBefore,
                        'slots_booked_after' => $slotsAfter,
                        'created_at' => $booking->created_at->toDateTimeString(),
                        'expired_at' => now()->toDateTimeString(),
                    ]);
                });

                $processedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to expire booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        if ($processedCount > 1000) {
            Log::warning('ExpirePendingBookingJob processed large number of bookings', [
                'count' => $processedCount,
            ]);
        }
    }
}
