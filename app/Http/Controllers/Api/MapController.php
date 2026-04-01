<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MapWorkoutsRequest;
use App\Http\Resources\WorkoutMapResource;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    /**
     * Get workouts for map display.
     */
    public function index(MapWorkoutsRequest $request): ResourceCollection
    {
        $startTime = microtime(true);
        $validated = $request->validated();

        $query = Workout::query()
            ->where('status', 'published')
            ->where('starts_at', '>', now())
            ->with([
                'sport',
                'coach.coachProfile',
                'coach.media',
                'city',
            ]);

        // Filter by city
        if ($request->filled('city_id')) {
            $query->where('city_id', $validated['city_id']);
        }

        // Filter by sport (supports single or multiple)
        if ($request->filled('sport_id')) {
            $sportIds = $validated['sport_id'];
            if (is_array($sportIds)) {
                $query->whereIn('sport_id', $sportIds);
            } else {
                $query->where('sport_id', $sportIds);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('starts_at', '>=', Carbon::parse($validated['date_from'])->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('starts_at', '<=', Carbon::parse($validated['date_to'])->endOfDay());
        }

        // Bounding box filter (viewport optimization)
        $bbox = null;
        if ($request->filled(['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'])) {
            $neLat = $validated['ne_lat'];
            $neLng = $validated['ne_lng'];
            $swLat = $validated['sw_lat'];
            $swLng = $validated['sw_lng'];

            $bbox = [
                'ne_lat' => $neLat,
                'ne_lng' => $neLng,
                'sw_lat' => $swLat,
                'sw_lng' => $swLng,
            ];

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
        // Query for limit+1 to detect truncation
        $limit = 200;
        $workouts = $query->limit($limit + 1)->get();
        $resultCount = $workouts->count();
        $wasTruncated = $resultCount > $limit;

        // If truncated, slice to limit
        if ($wasTruncated) {
            $workouts = $workouts->slice(0, $limit);
            $resultCount = $limit;
        }

        $requestDuration = round((microtime(true) - $startTime) * 1000, 2);

        // Log successful request with monitoring data
        $logContext = [
            'result_count' => $resultCount,
            'truncated' => $wasTruncated,
            'request_duration' => $requestDuration,
        ];

        if ($bbox) {
            $logContext['bbox'] = $bbox;
        }

        if ($request->filled('city_id')) {
            $logContext['city_id'] = $validated['city_id'];
        }

        if ($request->filled('sport_id')) {
            $logContext['sport_id'] = $validated['sport_id'];
        }

        Log::info('Map API: workouts loaded', $logContext);

        // Log performance warnings
        if ($wasTruncated) {
            Log::warning('Map API: result limit reached', $logContext);
        }

        if ($requestDuration > 1000) {
            Log::warning('Map API: slow query detected', $logContext);
        }

        return WorkoutMapResource::collection($workouts)->additional([
            'meta' => [
                'truncated' => $wasTruncated,
                'limit' => $limit,
            ],
        ]);
    }
}
