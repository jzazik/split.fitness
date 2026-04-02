<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoachPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Only exposes public-safe coach information to athletes.
     * Does not include email, phone, status, or internal timestamps.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'avatar_path' => $this->avatar_path,
        ];
    }
}
