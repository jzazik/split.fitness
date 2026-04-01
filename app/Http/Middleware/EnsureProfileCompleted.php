<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        if (! $this->isProfileCompleted($user)) {
            $redirectCount = session()->get('onboarding_redirect_count', 0);

            if ($redirectCount > 3) {
                Log::warning('Onboarding redirect loop detected - profile incomplete but cannot redirect', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'redirect_count' => $redirectCount,
                    'current_url' => $request->url(),
                ]);

                session()->forget('onboarding_redirect_count');
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'profile' => 'Профиль не завершён. Пожалуйста, войдите снова и завершите регистрацию.',
                ]);
            }

            session()->put('onboarding_redirect_count', $redirectCount + 1);

            return redirect()->route('onboarding.show');
        }

        session()->forget('onboarding_redirect_count');

        return $next($request);
    }

    /**
     * Check if the current route should be excluded from profile completion check.
     */
    protected function isExcludedRoute(Request $request): bool
    {
        $excludedPaths = [
            'onboarding',
            'profile',
            'logout',
        ];

        foreach ($excludedPaths as $path) {
            if ($request->is($path) || $request->is("*/{$path}") || $request->is("*/{$path}/*")) {
                return true;
            }
        }

        return false;
    }

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
