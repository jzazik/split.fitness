<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoach();
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', 'exists:sports,id,is_active,1'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'starts_at' => ['required', 'date', 'after:now', 'before:+1 year'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'total_price' => ['required', 'numeric', 'min:0.01'],
            'slots_total' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sport_id.required' => 'Выберите вид спорта',
            'sport_id.exists' => 'Выбранный вид спорта не найден',
            'city_id.required' => 'Выберите город',
            'city_id.exists' => 'Выбранный город не найден',
            'location_name.required' => 'Укажите название места проведения',
            'location_name.max' => 'Название места слишком длинное',
            'lat.required' => 'Выберите точку на карте',
            'lat.numeric' => 'Неверная широта',
            'lat.between' => 'Широта должна быть в диапазоне от -90 до 90',
            'lng.required' => 'Выберите точку на карте',
            'lng.numeric' => 'Неверная долгота',
            'lng.between' => 'Долгота должна быть в диапазоне от -180 до 180',
            'starts_at.required' => 'Укажите дату и время начала',
            'starts_at.date' => 'Неверный формат даты',
            'starts_at.after' => 'Дата начала должна быть в будущем',
            'duration_minutes.required' => 'Укажите длительность тренировки',
            'duration_minutes.integer' => 'Длительность должна быть целым числом',
            'duration_minutes.min' => 'Минимальная длительность - 1 минута',
            'duration_minutes.max' => 'Максимальная длительность - 8 часов',
            'total_price.required' => 'Укажите общую стоимость',
            'total_price.numeric' => 'Стоимость должна быть числом',
            'total_price.min' => 'Минимальная стоимость - 0.01 ₽',
            'slots_total.required' => 'Укажите количество мест',
            'slots_total.integer' => 'Количество мест должно быть целым числом',
            'slots_total.min' => 'Минимум 1 место',
            'slots_total.max' => 'Максимум 100 мест',
        ];
    }
}
