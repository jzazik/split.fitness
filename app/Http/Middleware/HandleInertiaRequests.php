<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function handle(Request $request, \Closure $next): mixed
    {
        if ($request->is('log-viewer*')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user && $user->role === 'coach') {
            $user->load('coachProfile');
        }

        $authData = ['user' => $user];

        if ($user) {
            $authData['avatar_url'] = $user->getFirstMediaUrl('avatar') ?: null;
            $authData['primary_sport_slug'] = $this->resolvePrimarySportSlug($user);
        }

        if ($user && $user->role === 'coach' && $user->coachProfile) {
            $authData['coachProfile'] = [
                'moderation_status' => $user->coachProfile->moderation_status,
                'rejection_reason' => $user->coachProfile->rejection_reason ?? null,
            ];
        }

        return [
            ...parent::share($request),
            'auth' => $authData,
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    private function resolvePrimarySportSlug(\App\Models\User $user): ?string
    {
        if ($user->isCoach()) {
            return $user->coachProfile
                ?->sports()
                ->value('slug');
        }

        return $user->bookings()
            ->join('workouts', 'workouts.id', '=', 'bookings.workout_id')
            ->join('sports', 'sports.id', '=', 'workouts.sport_id')
            ->orderBy('bookings.created_at')
            ->value('sports.slug');
    }
}
