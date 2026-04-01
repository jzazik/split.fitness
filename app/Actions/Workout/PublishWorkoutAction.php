<?php

namespace App\Actions\Workout;

use App\Models\Workout;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PublishWorkoutAction
{
    /**
     * Publish a workout.
     *
     * @throws ValidationException
     */
    public function execute(Workout $workout): void
    {
        // Check if already published
        if ($workout->status === 'published') {
            throw ValidationException::withMessages([
                'status' => 'Тренировка уже опубликована.',
            ]);
        }

        // Check if coach is approved
        $coach = $workout->coach;
        if (! $coach->coachProfile || $coach->coachProfile->moderation_status !== 'approved') {
            Log::error('Attempted to publish workout with non-approved coach', [
                'workout_id' => $workout->id,
                'coach_id' => $coach->id,
                'moderation_status' => $coach->coachProfile?->moderation_status ?? 'no_profile',
            ]);

            throw ValidationException::withMessages([
                'moderation_status' => 'Профиль тренера должен быть одобрен перед публикацией тренировки.',
            ]);
        }

        // Check if starts_at is at least 1 hour in the future
        if ($workout->starts_at->lessThan(now()->addHour())) {
            throw ValidationException::withMessages([
                'starts_at' => 'Тренировку можно публиковать только если она начинается минимум через 1 час.',
            ]);
        }

        // Update workout status
        $workout->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        Log::info('Workout published', [
            'workout_id' => $workout->id,
            'coach_id' => $coach->id,
            'status' => 'published',
            'starts_at' => $workout->starts_at->toIso8601String(),
            'slots_total' => $workout->slots_total,
        ]);
    }
}
