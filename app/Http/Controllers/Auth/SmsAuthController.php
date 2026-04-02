<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendSmsCodeRequest;
use App\Http\Requests\Auth\VerifySmsCodeRequest;
use App\Models\User;
use App\Services\Auth\SmsAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsAuthController extends Controller
{
    public function __construct(protected SmsAuthService $smsAuthService) {}

    public function sendCode(SendSmsCodeRequest $request): JsonResponse
    {
        $this->smsAuthService->sendCode($request->validated('phone'));

        return response()->json(['success' => true]);
    }

    public function verifyCode(VerifySmsCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->smsAuthService->verifyCode($validated['phone'], $validated['code']);

        $user = User::where('phone', $validated['phone'])->first();

        if (! $user) {
            $user = User::create([
                'phone' => $validated['phone'],
                'role' => 'athlete',
                'first_name' => null,
                'last_name' => null,
                'email' => null,
                'password' => null,
            ]);
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = match ($user->role) {
            'athlete' => route('athlete.bookings', absolute: false),
            'coach' => route('coach.dashboard', absolute: false),
            'admin' => route('admin.dashboard', absolute: false),
            default => '/',
        };

        return response()->json([
            'success' => true,
            'action' => 'login',
            'redirect' => $redirect,
        ]);
    }

    public function registerWithPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+7\d{10}$/', 'unique:users,phone'],
            'role' => ['required', 'string', 'in:athlete,coach'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => null,
            'password' => null,
            'phone_verified_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => route('onboarding.show', absolute: false),
        ]);
    }
}
