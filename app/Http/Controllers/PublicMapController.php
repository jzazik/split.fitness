<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkoutMapResource;
use App\Models\City;
use App\Models\Sport;
use App\Models\Workout;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PublicMapController extends Controller
{
    /**
     * Display the public map page.
     */
    public function index(): Response
    {
        $cities = City::select('id', 'name', 'lat', 'lng')
            ->orderBy('name')
            ->get();

        $sports = Sport::select('id', 'name', 'slug', 'icon')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $bookedWorkouts = [];
        if ($user = Auth::user()) {
            $workoutIds = $user->bookings()
                ->where('status', '!=', 'cancelled')
                ->whereHas('workout', fn ($q) => $q->where('starts_at', '>', now()))
                ->pluck('workout_id');

            if ($workoutIds->isNotEmpty()) {
                $bookedWorkouts = WorkoutMapResource::collection(
                    Workout::whereIn('id', $workoutIds)
                        ->where('status', 'published')
                        ->where('starts_at', '>', now())
                        ->with(['sport', 'coach.coachProfile', 'coach.media' => fn ($q) => $q->where('collection_name', 'avatar'), 'city'])
                        ->get()
                )->resolve();
            }
        }

        return Inertia::render('Public/Map/Index', [
            'cities' => $cities,
            'sports' => $sports,
            'bookedWorkouts' => $bookedWorkouts,
        ]);
    }
}
