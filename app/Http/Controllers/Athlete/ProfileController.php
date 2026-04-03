<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Http\Requests\Athlete\UpdateProfileRequest;
use App\Models\City;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $user = auth()->user();
        $user->load(['city', 'athleteProfile']);

        $cities = City::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Athlete/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'city_id' => $user->city_id,
                'city' => $user->city,
                'avatar_url' => $user->getFirstMediaUrl('avatar'),
            ],
            'profile' => $user->athleteProfile ? [
                'id' => $user->athleteProfile->id,
                'emergency_contact' => $user->athleteProfile->emergency_contact,
            ] : null,
            'cities' => $cities,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

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

            Log::info('Athlete profile updated', [
                'user_id' => $user->id,
                'role' => $user->role,
                'profile_type' => 'athlete',
            ]);
        });

        return redirect()->route('athlete.profile')->with('success', 'Профиль обновлён');
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:30720',
        ]);

        $user = auth()->user();
        $file = $request->file('avatar');

        try {
            $user->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');

            Log::info('Avatar uploaded', [
                'user_id' => $user->id,
                'role' => $user->role,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return redirect()->route('athlete.profile')->with('success', 'Фото загружено');
        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('athlete.profile')
                ->withErrors(['avatar' => 'Не удалось загрузить фото. Попробуйте ещё раз.']);
        }
    }

    public function deleteAvatar(): RedirectResponse
    {
        $user = auth()->user();

        try {
            $user->clearMediaCollection('avatar');

            Log::info('Avatar removed', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);

            return redirect()->route('athlete.profile')->with('success', 'Фото удалено');
        } catch (\Exception $e) {
            Log::error('Avatar removal failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('athlete.profile')
                ->withErrors(['avatar' => 'Не удалось удалить фото.']);
        }
    }
}
