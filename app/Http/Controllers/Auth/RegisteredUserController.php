<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'role' => $validated['role'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // Mask email for logging (keep first char + domain, hide rest)
        $emailParts = explode('@', $user->email);
        $maskedEmail = count($emailParts) === 2
            ? substr($emailParts[0], 0, 1).'***@'.$emailParts[1]
            : '***';

        Log::info('User registered successfully', [
            'user_id' => $user->id,
            'role' => $user->role,
            'email' => $maskedEmail,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Role-based redirect
        $redirectRoute = match ($user->role) {
            'athlete' => 'athlete.bookings',
            'coach' => 'coach.dashboard',
            'admin' => 'admin.dashboard',
            default => 'dashboard',
        };

        return redirect()->route($redirectRoute);
    }
}
