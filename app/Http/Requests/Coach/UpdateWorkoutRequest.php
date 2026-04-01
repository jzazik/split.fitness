<?php

namespace App\Http\Requests\Coach;

use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoach();
    }

    public function rules(): array
    {
        $workout = $this->route('workout');
        $minSlots = $workout ? $workout->slots_booked : 1;

        return [
            'sport_id' => ['required', 'integer', 'exists:sports,id,is_active,1'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_name' => ['required', 'string', 'min:10', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'starts_at' => ['required', 'date', 'after:now', 'before:+3 months', function ($attribute, $value, $fail) {
                $timestamp = strtotime($value);
                $minutes = (int) date('i', $timestamp);
                if ($minutes % 15 !== 0) {
                    $fail('Время должно быть кратно 15 минутам (например: 08:00, 08:15, 08:30)');
                }
            }],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'total_price' => ['required', 'numeric', 'min:100', 'max:50000'],
            'slots_total' => ['required', 'integer', "min:{$minSlots}", 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateWorkoutOverlap($validator);
            $this->validateMaxActiveWorkouts($validator);
        });
    }

    protected function validateWorkoutOverlap($validator): void
    {
        $workout = $this->route('workout');
        $startsAt = $this->input('starts_at');
        $durationMinutes = $this->input('duration_minutes');
        $lat = $this->input('lat');
        $lng = $this->input('lng');

        if (! $startsAt || ! $durationMinutes) {
            return;
        }

        $startsAt = Carbon::parse($startsAt);
        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

        // Check for overlapping workouts for this coach with 30-minute buffer
        $bufferStart = $startsAt->copy()->subMinutes(30);
        $bufferEnd = $endsAt->copy()->addMinutes(30);

        // Get all workouts for this coach and check overlap in PHP to avoid DB-specific SQL
        $hasOverlap = Workout::where('coach_id', $this->user()->id)
            ->where('id', '!=', $workout->id)
            ->whereIn('status', ['draft', 'published'])
            ->get()
            ->contains(function ($otherWorkout) use ($bufferStart, $bufferEnd) {
                $workoutStart = $otherWorkout->starts_at;
                $workoutEnd = $otherWorkout->starts_at->copy()->addMinutes($otherWorkout->duration_minutes);

                // Check if there's any overlap between buffered time ranges
                return $workoutStart < $bufferEnd && $workoutEnd > $bufferStart;
            });

        if ($hasOverlap) {
            $validator->errors()->add('starts_at', 'У вас уже есть тренировка в это время. Минимальный интервал между тренировками - 30 минут.');
        }

        // Check for same location and time conflicts
        if ($lat && $lng) {
            $sameLocationAndTime = Workout::where('coach_id', '!=', $this->user()->id)
                ->where('id', '!=', $workout->id)
                ->whereIn('status', ['draft', 'published'])
                ->where('starts_at', $startsAt->toDateTimeString())
                ->where('lat', $lat)
                ->where('lng', $lng)
                ->exists();

            if ($sameLocationAndTime) {
                $validator->errors()->add('starts_at', 'На это время и место уже запланирована другая тренировка.');
            }
        }
    }

    protected function validateMaxActiveWorkouts($validator): void
    {
        $workout = $this->route('workout');

        $activeCount = Workout::where('coach_id', $this->user()->id)
            ->where('id', '!=', $workout->id)
            ->whereIn('status', ['draft', 'published'])
            ->count();

        if ($activeCount >= 10) {
            $validator->errors()->add('status', 'Вы достигли максимального количества активных тренировок (10). Завершите или отмените существующие тренировки.');
        }
    }

    public function messages(): array
    {
        return [
            'sport_id.required' => 'Выберите вид спорта',
            'sport_id.exists' => 'Выбранный вид спорта не найден',
            'city_id.required' => 'Выберите город',
            'city_id.exists' => 'Выбранный город не найден',
            'location_name.required' => 'Укажите название места проведения',
            'location_name.min' => 'Описание места должно содержать минимум 10 символов',
            'location_name.max' => 'Название места слишком длинное',
            'lat.required' => 'Выберите точку на карте',
            'lat.numeric' => 'Неверная широта',
            'lat.between' => 'Широта должна быть в диапазоне от -90 до 90',
            'lng.required' => 'Выберите точку на карте',
            'lng.numeric' => 'Неверная долгота',
            'lng.between' => 'Долгота должна быть в диапазоне от -180 до 180',
            'starts_at.required' => 'Укажите дату и время начала',
            'starts_at.date' => 'Неверный формат даты',
            'starts_at.after' => 'Тренировка должна начинаться в будущем',
            'starts_at.before' => 'Тренировка должна начинаться не позже чем через 3 месяца',
            'duration_minutes.required' => 'Укажите длительность тренировки',
            'duration_minutes.integer' => 'Длительность должна быть целым числом',
            'duration_minutes.min' => 'Минимальная длительность - 1 минута',
            'duration_minutes.max' => 'Максимальная длительность - 8 часов',
            'total_price.required' => 'Укажите общую стоимость',
            'total_price.numeric' => 'Стоимость должна быть числом',
            'total_price.min' => 'Минимальная стоимость - 100 ₽',
            'total_price.max' => 'Максимальная стоимость - 50000 ₽',
            'slots_total.required' => 'Укажите количество мест',
            'slots_total.integer' => 'Количество мест должно быть целым числом',
            'slots_total.min' => $this->route('workout') && $this->route('workout')->slots_booked > 0
                ? 'Нельзя уменьшить количество мест ниже текущего количества бронирований ('.$this->route('workout')->slots_booked.')'
                : 'Минимум 1 место',
            'slots_total.max' => 'Максимум 50 мест',
        ];
    }
}
