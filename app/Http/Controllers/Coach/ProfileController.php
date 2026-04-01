<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\UpdateProfileRequest;
use App\Models\City;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        $user = auth()->user();
        $user->load(['city', 'coachProfile.sports']);

        $cities = City::orderBy('name')->get(['id', 'name']);
        $sports = Sport::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Coach/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
                'phone' => $user->phone,
                'city_id' => $user->city_id,
                'city' => $user->city,
                'avatar_url' => $user->getFirstMediaUrl('avatar'),
            ],
            'profile' => $user->coachProfile ? [
                'id' => $user->coachProfile->id,
                'bio' => $user->coachProfile->bio,
                'experience_years' => $user->coachProfile->experience_years,
                'moderation_status' => $user->coachProfile->moderation_status,
                'is_public' => $user->coachProfile->is_public,
                'sports' => $user->coachProfile->sports->map(fn ($sport) => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'slug' => $sport->slug,
                ]),
                'diplomas' => $user->coachProfile->getMedia('diplomas')->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                ]),
                'certificates' => $user->coachProfile->getMedia('certificates')->map(fn ($media) => [
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

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'city_id' => $validated['city_id'],
        ]);

        $profile = $user->coachProfile;
        $profile->update([
            'bio' => $validated['bio'],
            'experience_years' => $validated['experience_years'] ?? null,
        ]);

        $profile->sports()->sync($validated['sports']);

        Log::info('Coach profile updated', [
            'user_id' => $user->id,
            'role' => $user->role,
            'profile_type' => 'coach',
            'sports_count' => count($validated['sports']),
        ]);

        return redirect()->route('coach.profile')->with('success', 'Профиль обновлён');
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:5120',
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

            return redirect()->route('coach.profile')->with('success', 'Фото загружено');
        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('coach.profile')
                ->withErrors(['avatar' => 'Не удалось загрузить фото. Попробуйте ещё раз.']);
        }
    }

    public function uploadDiploma(Request $request): RedirectResponse
    {
        $request->validate([
            'diploma' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $user = auth()->user();
        $file = $request->file('diploma');

        try {
            $user->coachProfile->addMediaFromRequest('diploma')
                ->toMediaCollection('diplomas');

            Log::info('Diploma uploaded', [
                'user_id' => $user->id,
                'role' => $user->role,
                'profile_type' => 'coach',
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return redirect()->route('coach.profile')->with('success', 'Диплом загружен');
        } catch (\Exception $e) {
            Log::error('Diploma upload failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('coach.profile')
                ->withErrors(['diploma' => 'Не удалось загрузить диплом. Попробуйте ещё раз.']);
        }
    }
}
