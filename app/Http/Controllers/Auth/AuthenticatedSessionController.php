<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        // Capture the redirect query parameter and set as intended URL
        if ($request->has('redirect')) {
            $redirect = $request->input('redirect');

            // Validate that redirect is a local path (not absolute URL or protocol-relative URL)
            if (filter_var($redirect, FILTER_VALIDATE_URL) === false &&
                str_starts_with($redirect, '/') &&
                ! str_starts_with($redirect, '//')) {
                $request->session()->put('url.intended', $redirect);
            }
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        $defaultIntended = match ($user->role) {
            'athlete' => route('athlete.bookings', absolute: false),
            'coach' => route('coach.dashboard', absolute: false),
            'admin' => route('admin.dashboard', absolute: false),
            default => throw new \RuntimeException('Invalid user role: '.$user->role),
        };

        return redirect()->intended($defaultIntended);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
