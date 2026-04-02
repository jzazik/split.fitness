<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class WorkoutMapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Validate coordinates are within valid ranges
        $lat = $this->lat;
        $lng = $this->lng;

        if ($lat !== null && ($lat < -90 || $lat > 90)) {
            Log::warning('Invalid latitude detected in workout', [
                'workout_id' => $this->id,
                'lat' => $lat,
            ]);
            $lat = null;
        }

        if ($lng !== null && ($lng < -180 || $lng > 180)) {
            Log::warning('Invalid longitude detected in workout', [
                'workout_id' => $this->id,
                'lng' => $lng,
            ]);
            $lng = null;
        }

        return [
            'id' => $this->id,
            'lat' => $lat,
            'lng' => $lng,
            'sport_name' => $this->sport?->name,
            'sport_slug' => $this->sport?->slug,
            'location_name' => $this->location_name,
            'address' => $this->address,
            'city_name' => $this->city?->name,
            'starts_at' => $this->starts_at,
            'duration_minutes' => $this->duration_minutes,
            'slot_price' => $this->slot_price,
            'slots_total' => $this->slots_total,
            'slots_booked' => $this->slots_booked,
            'coach_name' => $this->coach?->full_name,
            'coach_avatar_url' => $this->coach?->media?->first()?->getUrl(),
            'coach_rating' => $this->coach?->coachProfile?->rating_avg,
        ];
    }
}
