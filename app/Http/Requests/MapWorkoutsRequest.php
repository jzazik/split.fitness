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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'city_id' => 'nullable|integer|exists:cities,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'ne_lat' => 'nullable|numeric|between:-90,90',
            'ne_lng' => 'nullable|numeric|between:-180,180',
            'sw_lat' => 'nullable|numeric|between:-90,90',
            'sw_lng' => 'nullable|numeric|between:-180,180',
        ];

        // sport_id can be single integer or array of integers
        if (is_array($this->input('sport_id'))) {
            $rules['sport_id'] = 'array';
            $rules['sport_id.*'] = 'integer|exists:sports,id';
        } else {
            $rules['sport_id'] = 'nullable|integer|exists:sports,id';
        }

        return $rules;
    }
}
