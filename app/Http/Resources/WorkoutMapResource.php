<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutMapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'sport_name' => $this->sport?->name,
            'starts_at' => $this->starts_at,
            'slot_price' => $this->slot_price,
            'slots_total' => $this->slots_total,
            'slots_booked' => $this->slots_booked,
            'coach_name' => $this->coach?->full_name,
            'coach_avatar_url' => $this->coach?->getFirstMediaUrl('avatar'),
            'coach_rating' => $this->coach?->coachProfile?->rating_avg,
        ];
    }
}
