<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MapWorkoutsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled(['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'])) {
            $lats = [(float) $this->input('sw_lat'), (float) $this->input('ne_lat')];
            $lngs = [(float) $this->input('sw_lng'), (float) $this->input('ne_lng')];

            $this->merge([
                'sw_lat' => min($lats),
                'ne_lat' => max($lats),
                'sw_lng' => min($lngs),
                'ne_lng' => max($lngs),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'city_id' => 'nullable|integer|exists:cities,id',
            'date_from' => 'nullable|date|after_or_equal:today',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'ne_lat' => 'nullable|numeric|between:-90,90',
            'ne_lng' => 'nullable|numeric|between:-180,180',
            'sw_lat' => 'nullable|numeric|between:-90,90',
            'sw_lng' => 'nullable|numeric|between:-180,180',
        ];

        if (is_array($this->input('sport_id'))) {
            $rules['sport_id'] = 'array';
            $rules['sport_id.*'] = 'integer|exists:sports,id';
        } else {
            $rules['sport_id'] = 'nullable|integer|exists:sports,id';
        }

        return $rules;
    }
}
