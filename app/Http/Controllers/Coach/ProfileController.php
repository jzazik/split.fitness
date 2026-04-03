<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\UpdateProfileRequest;
use App\Models\City;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;
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

            Log::info('Coach profile updated', [
                'user_id' => $user->id,
                'role' => $user->role,
                'profile_type' => 'coach',
                'sports_count' => count($validated['sports']),
            ]);
        });

        return redirect()->route('coach.profile')->with('success', 'Профиль обновлён');
    }

    public function uploadAvatar(Request $request): JsonResponse|RedirectResponse
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

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Фото загружено']);
            }

            return redirect()->route('coach.profile')->with('success', 'Фото загружено');
        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Не удалось загрузить фото. Попробуйте ещё раз.'], 422);
            }

            return redirect()->route('coach.profile')
                ->withErrors(['avatar' => 'Не удалось загрузить фото. Попробуйте ещё раз.']);
        }
    }

    public function uploadDiploma(Request $request)
    {
        $request->validate([
            'diplomas' => 'required|array',
            'diplomas.*' => 'file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png|max:10240',
        ]);

        $user = auth()->user();

        try {
            if (! $user->coachProfile) {
                throw new \Exception('Coach profile not found');
            }

            $files = $request->file('diplomas');
            foreach ($files as $file) {
                $user->coachProfile
                    ->addMedia($file)
                    ->toMediaCollection('diplomas');

                Log::info('Diploma uploaded', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'profile_type' => 'coach',
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Дипломы загружены']);
            }

            return redirect()->route('coach.profile')->with('success', 'Дипломы загружены');
        } catch (\Exception $e) {
            Log::error('Diploma upload failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Не удалось загрузить дипломы. Попробуйте ещё раз.'], 422);
            }

            return redirect()->route('coach.profile')
                ->withErrors(['diplomas' => 'Не удалось загрузить дипломы. Попробуйте ещё раз.']);
        }
    }

    public function uploadCertificate(Request $request)
    {
        $request->validate([
            'certificates' => 'required|array',
            'certificates.*' => 'file|mimes:pdf,jpg,jpeg,png|mimetypes:application/pdf,image/jpeg,image/png|max:10240',
        ]);

        $user = auth()->user();

        try {
            if (! $user->coachProfile) {
                throw new \Exception('Coach profile not found');
            }

            $files = $request->file('certificates');
            foreach ($files as $file) {
                $user->coachProfile
                    ->addMedia($file)
                    ->toMediaCollection('certificates');

                Log::info('Certificate uploaded', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'profile_type' => 'coach',
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Справки загружены']);
            }

            return redirect()->route('coach.profile')->with('success', 'Справки загружены');
        } catch (\Exception $e) {
            Log::error('Certificate upload failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Не удалось загрузить справки. Попробуйте ещё раз.'], 422);
            }

            return redirect()->route('coach.profile')
                ->withErrors(['certificates' => 'Не удалось загрузить справки. Попробуйте ещё раз.']);
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

            return redirect()->route('coach.profile')->with('success', 'Фото удалено');
        } catch (\Exception $e) {
            Log::error('Avatar removal failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('coach.profile')
                ->withErrors(['avatar' => 'Не удалось удалить фото.']);
        }
    }

    public function deleteDiploma(int $mediaId): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->coachProfile) {
            return redirect()->route('coach.profile')
                ->withErrors(['diploma' => 'Профиль не найден.']);
        }

        try {
            $media = $user->coachProfile->getMedia('diplomas')->firstWhere('id', $mediaId);

            if (! $media) {
                return redirect()->route('coach.profile')
                    ->withErrors(['diploma' => 'Файл не найден.']);
            }

            $media->delete();

            Log::info('Diploma removed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'media_id' => $mediaId,
            ]);

            return redirect()->route('coach.profile')->with('success', 'Диплом удалён');
        } catch (\Exception $e) {
            Log::error('Diploma removal failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('coach.profile')
                ->withErrors(['diploma' => 'Не удалось удалить диплом.']);
        }
    }

    public function deleteCertificate(int $mediaId): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->coachProfile) {
            return redirect()->route('coach.profile')
                ->withErrors(['certificate' => 'Профиль не найден.']);
        }

        try {
            $media = $user->coachProfile->getMedia('certificates')->firstWhere('id', $mediaId);

            if (! $media) {
                return redirect()->route('coach.profile')
                    ->withErrors(['certificate' => 'Файл не найден.']);
            }

            $media->delete();

            Log::info('Certificate removed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'media_id' => $mediaId,
            ]);

            return redirect()->route('coach.profile')->with('success', 'Справка удалена');
        } catch (\Exception $e) {
            Log::error('Certificate removal failed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('coach.profile')
                ->withErrors(['certificate' => 'Не удалось удалить справку.']);
        }
    }

    public function resubmit(): RedirectResponse
    {
        $user = auth()->user();
        $profile = $user->coachProfile;

        if (! $profile) {
            return redirect()->route('home')
                ->withErrors(['profile' => 'Профиль не найден.']);
        }

        if ($profile->moderation_status !== 'rejected') {
            return redirect()->route('home')
                ->withErrors(['profile' => 'Профиль не был отклонён.']);
        }

        DB::transaction(function () use ($profile, $user) {
            $profile->update([
                'moderation_status' => 'pending',
                'rejection_reason' => null,
            ]);

            Log::info('Coach profile resubmitted for moderation', [
                'user_id' => $user->id,
                'role' => $user->role,
                'profile_type' => 'coach',
                'moderation_status' => 'pending',
            ]);
        });

        return redirect()->route('home')
            ->with('success', 'Профиль отправлен на повторную проверку.');
    }
}
