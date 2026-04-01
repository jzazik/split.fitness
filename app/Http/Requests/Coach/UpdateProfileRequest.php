<?php

namespace App\Http\Requests\Coach;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCoach();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:1000'],
            'city_id' => ['required', 'exists:cities,id'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => ['required', 'integer', 'exists:sports,id,is_active,1'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Имя обязательно для заполнения',
            'last_name.required' => 'Фамилия обязательна для заполнения',
            'bio.required' => 'Расскажите о себе',
            'bio.max' => 'Описание не должно превышать 1000 символов',
            'city_id.required' => 'Выберите город',
            'city_id.exists' => 'Выбранный город не существует',
            'sports.required' => 'Выберите хотя бы один вид спорта',
            'sports.min' => 'Выберите хотя бы один вид спорта',
            'sports.*.exists' => 'Один из выбранных видов спорта не существует',
            'experience_years.integer' => 'Опыт должен быть числом',
            'experience_years.min' => 'Опыт не может быть отрицательным',
        ];
    }
}
