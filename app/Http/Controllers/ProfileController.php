<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Sport;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'avatarUrl' => $user->getFirstMediaUrl('avatar') ?: null,
            'sports' => Sport::where('is_active', true)->orderBy('name')->get(['id', 'slug', 'name']),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:30720',
        ]);

        $user = $request->user();

        try {
            $user->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');

            Log::info('Avatar uploaded', [
                'user_id' => $user->id,
                'role' => $user->role,
            ]);

            return Redirect::route('profile.edit')->with('success', 'Фото загружено');
        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::route('profile.edit')
                ->withErrors(['avatar' => 'Не удалось загрузить фото. Попробуйте ещё раз.']);
        }
    }

    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $user->clearMediaCollection('avatar');

            Log::info('Avatar removed', ['user_id' => $user->id]);

            return Redirect::route('profile.edit')->with('success', 'Фото удалено');
        } catch (\Exception $e) {
            Log::error('Avatar removal failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return Redirect::route('profile.edit')
                ->withErrors(['avatar' => 'Не удалось удалить фото.']);
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        try {
            $user->delete();
        } catch (QueryException $e) {
            // Handle foreign key constraint violation gracefully
            if ($e->getCode() === '23000') {
                $errorMessage = match ($user->role) {
                    'athlete' => 'Невозможно удалить аккаунт. У вас есть активные или прошлые записи на тренировки. Пожалуйста, свяжитесь с поддержкой для удаления аккаунта.',
                    'coach' => 'Невозможно удалить аккаунт. У вас есть тренировки с записями атлетов. Пожалуйста, свяжитесь с поддержкой для удаления аккаунта.',
                    default => 'Невозможно удалить аккаунт. Пожалуйста, свяжитесь с поддержкой.',
                };

                // Re-authenticate the user since we logged them out
                Auth::login($user);

                return Redirect::route('profile.edit')->withErrors([
                    'account_deletion' => $errorMessage,
                ]);
            }

            throw $e;
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
