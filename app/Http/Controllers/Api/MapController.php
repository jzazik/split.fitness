<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MapWorkoutsRequest;
use App\Http\Resources\WorkoutMapResource;
use App\Models\Workout;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    /**
     * Get workouts for map display.
     */
    public function index(MapWorkoutsRequest $request): ResourceCollection
    {
        $query = Workout::query()
            ->where('status', 'published')
            ->where('starts_at', '>', now())
            ->with([
                'sport',
                'coach.coachProfile',
                'city',
            ]);

        // Filter by city
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }

        // Filter by sport (supports single or multiple)
        if ($request->filled('sport_id')) {
            $sportIds = $request->input('sport_id');
            if (is_array($sportIds)) {
                $query->whereIn('sport_id', $sportIds);
            } else {
                $query->where('sport_id', $sportIds);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('starts_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('starts_at', '<=', $request->input('date_to'));
        }

        // Bounding box filter (viewport optimization)
        if ($request->filled(['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'])) {
            $neLat = $request->validated('ne_lat');
            $neLng = $request->validated('ne_lng');
            $swLat = $request->validated('sw_lat');
            $swLng = $request->validated('sw_lng');

            $query->whereBetween('lat', [$swLat, $neLat]);

            // Handle dateline wrapping for longitude
            if ($swLng > $neLng) {
                // Crosses dateline (e.g., eastern Russia, Pacific islands)
                $query->where(function ($q) use ($swLng, $neLng) {
                    $q->where('lng', '>=', $swLng)
                        ->orWhere('lng', '<=', $neLng);
                });
            } else {
                $query->whereBetween('lng', [$swLng, $neLng]);
            }
        }

        // Limit results to prevent overload
        $limit = 200;
        $workouts = $query->limit($limit)->get();
        $resultCount = $workouts->count();

        // Log performance warning if result count is high
        if ($resultCount >= $limit) {
            Log::warning('Map API: result limit reached', [
                'result_count' => $resultCount,
                'limit' => $limit,
                'city_id' => $request->validated('city_id'),
                'sport_id' => $request->validated('sport_id'),
            ]);
        }

        return WorkoutMapResource::collection($workouts);
    }
}
