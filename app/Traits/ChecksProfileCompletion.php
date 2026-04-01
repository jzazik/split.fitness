<?php

namespace App\Traits;

trait ChecksProfileCompletion
{
    /**
     * Check if the user's profile is completed.
     */
    protected function isProfileCompleted($user): bool
    {
        if ($user->isCoach()) {
            $profile = $user->loadMissing('coachProfile.sports')->coachProfile;

            if (! $profile) {
                return false;
            }

            return ! empty($profile->bio)
                && strlen($profile->bio) >= 10
                && $profile->sports->count() > 0
                && ! empty($user->city_id);
        }

        if ($user->isAthlete()) {
            $profile = $user->athleteProfile;

            if (! $profile) {
                return false;
            }

            return ! empty($user->first_name) && ! empty($user->last_name);
        }

        return true;
    }
}
