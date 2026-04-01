<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(): Response
    {
        $user = auth()->user();

        // Redirect if profile is already completed
        if ($user->isCoach() && $user->coachProfile && $this->isProfileCompleted($user)) {
            return redirect()->route('coach.dashboard');
        }

        if ($user->isAthlete() && $user->athleteProfile && $this->isProfileCompleted($user)) {
            return redirect()->route('athlete.bookings');
        }

        $cities = City::orderBy('name')->get(['id', 'name']);
        $sports = Sport::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        if ($user->isCoach()) {
            return Inertia::render('Onboarding/Coach', [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'middle_name' => $user->middle_name,
                    'city_id' => $user->city_id,
                    'avatar_url' => $user->getFirstMediaUrl('avatar'),
                ],
                'profile' => $user->coachProfile ? [
                    'bio' => $user->coachProfile->bio,
                    'experience_years' => $user->coachProfile->experience_years,
                    'sports' => $user->coachProfile->sports->pluck('id')->toArray(),
                    'diplomas' => $user->coachProfile->getMedia('diplomas')->map(fn ($media) => [
                        'id' => $media->id,
                        'name' => $media->file_name,
                        'url' => $media->getUrl(),
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                    ]),
                ] : null,
                'cities' => $cities,
                'sports' => $sports,
            ]);
        }

        if ($user->isAthlete()) {
            return Inertia::render('Onboarding/Athlete', [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'city_id' => $user->city_id,
                    'avatar_url' => $user->getFirstMediaUrl('avatar'),
                ],
                'profile' => $user->athleteProfile ? [
                    'emergency_contact' => $user->athleteProfile->emergency_contact,
                ] : null,
                'cities' => $cities,
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isCoach()) {
            return $this->storeCoachProfile($request, $user);
        }

        if ($user->isAthlete()) {
            return $this->storeAthleteProfile($request, $user);
        }

        return redirect()->route('dashboard');
    }

    protected function storeCoachProfile(Request $request, $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'bio' => 'required|string|min:10|max:1000',
            'city_id' => 'required|exists:cities,id',
            'sports' => 'required|array|min:1',
            'sports.*' => 'exists:sports,id,is_active,1',
            'experience_years' => 'nullable|integer|min:0|max:100',
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->update([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'middle_name' => $validated['middle_name'] ?? null,
                    'city_id' => $validated['city_id'],
                ]);

                $profile = $user->coachProfile;

                if (! $profile) {
                    Log::warning('Coach profile was not auto-created, creating now', [
                        'user_id' => $user->id,
                        'role' => $user->role,
                    ]);

                    $profile = $user->coachProfile()->create([
                        'moderation_status' => 'pending',
                    ]);
                }

                $profile->update([
                    'bio' => $validated['bio'],
                    'experience_years' => $validated['experience_years'] ?? null,
                ]);

                $profile->sports()->sync($validated['sports']);

                Log::info('Coach profile completed via onboarding', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'profile_type' => 'coach',
                    'sports_count' => count($validated['sports']),
                ]);
            });

            return redirect()->route('coach.dashboard')->with('success', 'Профиль заполнен! Он будет проверен администратором.');
        } catch (\Exception $e) {
            Log::error('Failed to complete coach onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось сохранить профиль. Пожалуйста, попробуйте снова.']);
        }
    }

    protected function storeAthleteProfile(Request $request, $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'city_id' => 'nullable|exists:cities,id',
            'emergency_contact' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->update([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'phone' => $validated['phone'] ?? null,
                    'city_id' => $validated['city_id'] ?? null,
                ]);

                $profile = $user->athleteProfile;

                if (! $profile) {
                    Log::warning('Athlete profile was not auto-created, creating now', [
                        'user_id' => $user->id,
                        'role' => $user->role,
                    ]);

                    $profile = $user->athleteProfile()->create([]);
                }

                $profile->update([
                    'emergency_contact' => $validated['emergency_contact'] ?? null,
                ]);

                Log::info('Athlete profile completed via onboarding', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'profile_type' => 'athlete',
                ]);
            });

            return redirect()->route('athlete.bookings')->with('success', 'Профиль заполнен!');
        } catch (\Exception $e) {
            Log::error('Failed to complete athlete onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось сохранить профиль. Пожалуйста, попробуйте снова.']);
        }
    }
}
