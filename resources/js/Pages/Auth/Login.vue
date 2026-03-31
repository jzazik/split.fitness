<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Log in" />

        <div class="flex min-h-[calc(100vh-12rem)] items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="bg-white px-8 py-10 shadow-md rounded-lg">
                    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">Вход</h2>

                    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit">
            <div>
                <Input
                    id="email"
                    type="email"
                    label="Email"
                    v-model="form.email"
                    :error="form.errors.email"
                    required
                    autofocus
                    autocomplete="username"
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
                    autocomplete="current-password"
                />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600"
                        >Запомнить меня</span
                    >
                </label>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-primary-600 hover:text-primary-700 underline"
                >
                    Забыли пароль?
                </Link>

                <Button
                    type="submit"
                    variant="primary"
                    :disabled="form.processing"
                >
                    Войти
                </Button>
            </div>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
