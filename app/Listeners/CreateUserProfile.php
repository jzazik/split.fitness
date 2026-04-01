<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\AthleteProfile;
use App\Models\CoachProfile;
use Illuminate\Support\Facades\Log;

class CreateUserProfile
{
    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        try {
            \DB::transaction(function () use ($user) {
                if ($user->role === 'coach') {
                    CoachProfile::create([
                        'user_id' => $user->id,
                        'moderation_status' => 'pending',
                        'is_public' => false,
                        'rating_avg' => 0,
                        'rating_count' => 0,
                    ]);

                    Log::info('Coach profile created', [
                        'user_id' => $user->id,
                        'role' => 'coach',
                        'moderation_status' => 'pending',
                    ]);
                } elseif ($user->role === 'athlete') {
                    AthleteProfile::create([
                        'user_id' => $user->id,
                    ]);

                    Log::info('Athlete profile created', [
                        'user_id' => $user->id,
                        'role' => 'athlete',
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::critical('Failed to create user profile during registration', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
