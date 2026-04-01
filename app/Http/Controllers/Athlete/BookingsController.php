<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BookingsController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $allBookings = Booking::where('athlete_id', $user->id)
            ->with([
                'workout.sport',
                'workout.coach',
                'workout.city',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $now = Carbon::now();

        $upcoming = $allBookings->filter(function ($booking) use ($now) {
            return in_array($booking->status, ['pending_payment', 'paid']) &&
                   $booking->workout->starts_at > $now;
        })->values();

        $past = $allBookings->filter(function ($booking) use ($now) {
            return in_array($booking->status, ['paid']) &&
                   $booking->workout->starts_at <= $now;
        })->values();

        $cancelled = $allBookings->filter(function ($booking) {
            return in_array($booking->status, ['cancelled', 'expired', 'refunded']);
        })->values();

        return Inertia::render('Athlete/Bookings/Index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'cancelled' => $cancelled,
        ]);
    }
}
