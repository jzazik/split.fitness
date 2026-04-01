<?php

namespace App\Http\Requests\Athlete;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAthlete();
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'city_id' => ['nullable', 'exists:cities,id'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Имя обязательно для заполнения',
            'last_name.required' => 'Фамилия обязательна для заполнения',
            'phone.unique' => 'Этот номер телефона уже используется',
            'city_id.exists' => 'Выбранный город не существует',
        ];
    }
}
