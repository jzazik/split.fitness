<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CreateBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Models\Workout;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        protected CreateBookingAction $createBookingAction
    ) {}

    /**
     * Create a new booking.
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $workout = Workout::findOrFail($validated['workout_id']);
            $athlete = $request->user();
            $slotsCount = $validated['slots_count'];

            $booking = $this->createBookingAction->execute($workout, $athlete, $slotsCount);

            return response()->json([
                'booking' => [
                    'id' => $booking->id,
                    'workout_id' => $booking->workout_id,
                    'slots_count' => $booking->slots_count,
                    'total_amount' => $booking->total_amount,
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                    'booked_at' => $booking->booked_at,
                ],
                'payment_url' => null, // Placeholder for Sprint 6
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'workout_id' => $validated['workout_id'] ?? null,
                'athlete_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Не удалось создать бронирование. Попробуйте позже.',
            ], 500);
        }
    }
}
