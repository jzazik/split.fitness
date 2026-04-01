<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkoutMapResource;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    /**
     * Get workouts for map display.
     *
     * @param  Request  $request
     * @return ResourceCollection
     */
    public function index(Request $request): ResourceCollection
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

        // Filter by sport
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->input('sport_id'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('starts_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('starts_at', '<=', $request->input('date_to'));
        }

        // Bounding box filter (viewport optimization)
        if ($request->filled(['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'])) {
            $neLat = $request->input('ne_lat');
            $neLng = $request->input('ne_lng');
            $swLat = $request->input('sw_lat');
            $swLng = $request->input('sw_lng');

            $query->whereBetween('lat', [$swLat, $neLat])
                ->whereBetween('lng', [$swLng, $neLng]);

            Log::debug('Map API: bbox filter applied', [
                'bbox' => compact('neLat', 'neLng', 'swLat', 'swLng'),
            ]);
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
                'city_id' => $request->input('city_id'),
                'sport_id' => $request->input('sport_id'),
            ]);
        }

        // Log successful request
        Log::info('Map API: workouts loaded', [
            'result_count' => $resultCount,
            'city_id' => $request->input('city_id'),
            'sport_id' => $request->input('sport_id'),
            'has_bbox' => $request->filled(['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng']),
        ]);

        return WorkoutMapResource::collection($workouts);
    }
}
