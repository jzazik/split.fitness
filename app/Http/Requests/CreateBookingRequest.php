<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Workout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workout_id' => 'required|integer|exists:workouts,id',
            'slots_count' => 'required|integer|min:1|max:100',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $workoutId = $this->input('workout_id');
            if (! $workoutId) {
                return;
            }

            $workout = Workout::find($workoutId);

            if (! $workout) {
                return;
            }

            // Check if workout is published
            if ($workout->status !== 'published') {
                $validator->errors()->add('workout_id', 'Тренировка не опубликована');
            }

            // Check if workout hasn't started yet
            if ($workout->starts_at <= now()) {
                $validator->errors()->add('workout_id', 'Тренировка уже началась или завершилась');
            }

            $existingBooking = Booking::where('workout_id', $workoutId)
                ->where('athlete_id', $this->user()->id)
                ->whereIn('status', ['pending_payment', 'paid'])
                ->first();

            if ($existingBooking?->status === 'paid') {
                $validator->errors()->add('workout_id', 'Вы уже записаны на эту тренировку');

                return;
            }

            // Skip capacity check if retrying payment for existing pending booking
            if ($existingBooking?->status === 'pending_payment') {
                return;
            }

            $slotsCount = (int) $this->input('slots_count', 1);
            $availableSlots = $workout->slots_total - $workout->slots_booked;
            if ($slotsCount > $availableSlots) {
                $validator->errors()->add('slots_count', "Недостаточно свободных мест. Доступно: {$availableSlots}");
            }

            // Note: Critical checks are also performed in CreateBookingAction within transaction
            // for final TOCTOU protection
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default slots_count to 1 if not provided
        if (! $this->has('slots_count')) {
            $this->merge(['slots_count' => 1]);
        }
    }
}
