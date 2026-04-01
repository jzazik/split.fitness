<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    /**
     * Determine whether the user can view any workouts.
     */
    public function viewAny(User $user): bool
    {
        return $user->isCoach();
    }

    /**
     * Determine whether the user can create workouts.
     */
    public function create(User $user): bool
    {
        return $user->isCoach();
    }

    /**
     * Determine whether the user can publish the workout.
     */
    public function publish(User $user, Workout $workout): bool
    {
        if ($user->id !== $workout->coach_id) {
            return false;
        }

        // Ensure coachProfile is loaded
        $user->loadMissing('coachProfile');

        return $user->coachProfile !== null
            && $user->coachProfile->moderation_status === 'approved';
    }

    /**
     * Determine whether the user can update the workout.
     */
    public function update(User $user, Workout $workout): bool
    {
        return $user->id === $workout->coach_id
            && in_array($workout->status, ['draft', 'published']);
    }

    /**
     * Determine whether the user can cancel the workout.
     */
    public function cancel(User $user, Workout $workout): bool
    {
        return $user->id === $workout->coach_id
            && in_array($workout->status, ['draft', 'published']);
    }
}
