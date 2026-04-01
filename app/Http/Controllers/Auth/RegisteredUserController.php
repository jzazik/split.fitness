<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
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

        $user = \DB::transaction(function () use ($validated, $request) {
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
            $request->session()->regenerate();

            return $user;
        });

        // Dispatch profile creation event after transaction commits
        event(new UserRegistered($user));

        // Redirect to onboarding - the onboarding controller will redirect completed profiles to their dashboard
        return redirect()->route('onboarding.show');
    }
}
