<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    /**
     * Determine whether the user can publish the workout.
     *
     * @param User $user
     * @param Workout $workout
     * @return bool
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
     *
     * @param User $user
     * @param Workout $workout
     * @return bool
     */
    public function update(User $user, Workout $workout): bool
    {
        return $user->id === $workout->coach_id;
    }
}
