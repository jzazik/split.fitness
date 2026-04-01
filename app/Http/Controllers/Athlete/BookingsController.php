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
        $now = Carbon::now();

        $upcoming = Booking::where('athlete_id', $user->id)
            ->whereIn('status', ['pending_payment', 'paid'])
            ->whereHas('workout', function ($query) use ($now) {
                $query->where('starts_at', '>', $now);
            })
            ->with([
                'workout.sport',
                'workout.coach',
                'workout.city',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $past = Booking::where('athlete_id', $user->id)
            ->where('status', 'paid')
            ->whereHas('workout', function ($query) use ($now) {
                $query->where('starts_at', '<=', $now);
            })
            ->with([
                'workout.sport',
                'workout.coach',
                'workout.city',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $cancelled = Booking::where('athlete_id', $user->id)
            ->whereIn('status', ['cancelled', 'expired', 'refunded'])
            ->with([
                'workout.sport',
                'workout.coach',
                'workout.city',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Athlete/Bookings/Index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'cancelled' => $cancelled,
        ]);
    }

    public function show(Booking $booking): Response
    {
        $this->authorize('view', $booking);

        // Load all necessary relationships
        $booking->load([
            'workout.sport',
            'workout.coach',
            'workout.city',
        ]);

        return Inertia::render('Athlete/Bookings/Show', [
            'booking' => $booking,
        ]);
    }
}
