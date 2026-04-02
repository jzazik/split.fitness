<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
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

    /**
     * Delete the user's account.
     */
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
