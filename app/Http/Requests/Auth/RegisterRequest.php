<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
        return [
            'role' => ['required', 'string', 'in:athlete,coach'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-\(\)]+$/', 'max:20', 'unique:users,phone'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Выберите роль (Атлет или Тренер)',
            'role.in' => 'Недопустимая роль',
            'first_name.required' => 'Введите имя',
            'last_name.required' => 'Введите фамилию',
            'email.required' => 'Введите email',
            'email.email' => 'Введите корректный email',
            'email.unique' => 'Этот email уже зарегистрирован',
            'phone.regex' => 'Введите корректный номер телефона',
            'phone.unique' => 'Этот телефон уже зарегистрирован',
            'password.required' => 'Введите пароль',
            'password.confirmed' => 'Пароли не совпадают',
        ];
    }
}
