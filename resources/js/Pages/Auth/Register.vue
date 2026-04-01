<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    role: 'athlete',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Register" />

        <div class="flex min-h-[calc(100vh-12rem)] items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="bg-white px-8 py-10 shadow-md rounded-lg">
                    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">Регистрация</h2>

                    <form @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Я хочу</label>

                <div class="space-y-2">
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="radio"
                            name="role"
                            value="athlete"
                            v-model="form.role"
                            class="rounded-full border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                        />
                        <span class="ms-2 text-sm text-gray-700">Тренироваться (Атлет)</span>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input
                            type="radio"
                            name="role"
                            value="coach"
                            v-model="form.role"
                            class="rounded-full border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                        />
                        <span class="ms-2 text-sm text-gray-700">Проводить тренировки (Тренер)</span>
                    </label>
                </div>

                <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <div class="mt-4">
                <Input
                    id="first_name"
                    type="text"
                    label="Имя"
                    v-model="form.first_name"
                    :error="form.errors.first_name"
                    required
                    autofocus
                    autocomplete="given-name"
                />
            </div>

            <div class="mt-4">
                <Input
                    id="last_name"
                    type="text"
                    label="Фамилия"
                    v-model="form.last_name"
                    :error="form.errors.last_name"
                    required
                    autocomplete="family-name"
                />
            </div>

            <div class="mt-4">
                <Input
                    id="email"
                    type="email"
                    label="Email"
                    v-model="form.email"
                    :error="form.errors.email"
                    required
                    autocomplete="username"
                />
            </div>

            <div class="mt-4">
                <Input
                    id="phone"
                    type="tel"
                    label="Телефон (необязательно)"
                    v-model="form.phone"
                    :error="form.errors.phone"
                    autocomplete="tel"
                    placeholder="+7 (999) 123-45-67"
                />
            </div>

            <div class="mt-4">
                <Input
                    id="password"
                    type="password"
                    label="Пароль"
                    v-model="form.password"
                    :error="form.errors.password"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="mt-4">
                <Input
                    id="password_confirmation"
                    type="password"
                    label="Подтвердите пароль"
                    v-model="form.password_confirmation"
                    :error="form.errors.password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="text-sm text-primary-600 hover:text-primary-700 underline"
                >
                    Уже зарегистрированы?
                </Link>

                <Button
                    type="submit"
                    variant="primary"
                    :disabled="form.processing"
                >
                    Регистрация
                </Button>
            </div>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
