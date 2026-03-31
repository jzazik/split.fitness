<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
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
                <InputLabel for="role" value="Я хочу" />

                <div class="mt-2 space-y-2">
                    <label class="flex items-center">
                        <input
                            type="radio"
                            name="role"
                            value="athlete"
                            v-model="form.role"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <span class="ms-2 text-sm text-gray-700">Тренироваться (Атлет)</span>
                    </label>

                    <label class="flex items-center">
                        <input
                            type="radio"
                            name="role"
                            value="coach"
                            v-model="form.role"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <span class="ms-2 text-sm text-gray-700">Проводить тренировки (Тренер)</span>
                    </label>
                </div>

                <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <div class="mt-4">
                <InputLabel for="first_name" value="Имя" />

                <TextInput
                    id="first_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.first_name"
                    required
                    autofocus
                    autocomplete="given-name"
                />

                <InputError class="mt-2" :message="form.errors.first_name" />
            </div>

            <div class="mt-4">
                <InputLabel for="last_name" value="Фамилия" />

                <TextInput
                    id="last_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.last_name"
                    required
                    autocomplete="family-name"
                />

                <InputError class="mt-2" :message="form.errors.last_name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="phone" value="Телефон (необязательно)" />

                <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="form.phone"
                    autocomplete="tel"
                    placeholder="+7 (999) 123-45-67"
                />

                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Пароль" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Подтвердите пароль"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Уже зарегистрированы?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Регистрация
                </PrimaryButton>
            </div>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
