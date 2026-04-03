<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useSmsAuth } from '@/composables/useSmsAuth.js';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const authMethod = ref('sms');
const sms = useSmsAuth();

const registerForm = ref({
    role: 'athlete',
    first_name: '',
    last_name: '',
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

const submitRegister = () => {
    sms.registerWithPhone(registerForm.value);
};
</script>

<template>
    <PublicLayout>
        <Head title="Вход" />

        <div class="flex min-h-[calc(100vh-12rem)] items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="bg-white px-8 py-10 shadow-md rounded-lg">
                    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">Вход</h2>

                    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                        {{ status }}
                    </div>

                    <!-- Auth method tabs -->
                    <div class="flex rounded-lg bg-gray-100 p-1 mb-6">
                        <button
                            type="button"
                            @click="authMethod = 'sms'"
                            :class="[
                                'flex-1 rounded-md py-2 text-sm font-medium transition-colors',
                                authMethod === 'sms'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            По телефону
                        </button>
                        <button
                            type="button"
                            @click="authMethod = 'email'"
                            :class="[
                                'flex-1 rounded-md py-2 text-sm font-medium transition-colors',
                                authMethod === 'email'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            По email
                        </button>
                    </div>

                    <!-- SMS Auth -->
                    <div v-if="authMethod === 'sms'">
                        <!-- Step 1: Phone -->
                        <form v-if="sms.step.value === 'phone'" @submit.prevent="sms.sendCode()">
                            <Input
                                id="phone"
                                type="tel"
                                label="Номер телефона"
                                v-model="sms.phone.value"
                                :error="sms.errors.value.phone?.[0]"
                                required
                                autofocus
                                autocomplete="tel"
                                inputmode="numeric"
                                placeholder="+7"
                                @input="sms.onPhoneInput()"
                            />

                            <div class="mt-6">
                                <Button
                                    type="submit"
                                    variant="primary"
                                    class="w-full"
                                    :disabled="sms.loading.value || !sms.phone.value"
                                >
                                    {{ sms.loading.value ? 'Отправка...' : 'Получить код' }}
                                </Button>
                            </div>
                        </form>

                        <!-- Step 2: Code verification -->
                        <form v-else-if="sms.step.value === 'code'" @submit.prevent="sms.verifyCode()">
                            <p class="text-sm text-gray-600 mb-4">
                                Код отправлен на <span class="font-medium">{{ sms.phoneFormatted.value }}</span>
                            </p>

                            <Input
                                id="code"
                                type="text"
                                label="Код из СМС"
                                v-model="sms.code.value"
                                :error="sms.errors.value.code?.[0]"
                                required
                                autofocus
                                autocomplete="one-time-code"
                                placeholder="123321"
                                inputmode="numeric"
                                maxlength="6"
                            />

                            <div class="mt-6 space-y-3">
                                <Button
                                    type="submit"
                                    variant="primary"
                                    class="w-full"
                                    :disabled="sms.loading.value || sms.code.value.length !== 6"
                                >
                                    {{ sms.loading.value ? 'Проверка...' : 'Подтвердить' }}
                                </Button>

                                <div class="flex items-center justify-between">
                                    <button
                                        type="button"
                                        @click="sms.goBack()"
                                        class="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Изменить номер
                                    </button>

                                    <button
                                        type="button"
                                        @click="sms.sendCode()"
                                        :disabled="sms.cooldown.value > 0"
                                        class="text-sm text-primary-600 hover:text-primary-700 disabled:text-gray-400 disabled:cursor-not-allowed"
                                    >
                                        {{ sms.cooldown.value > 0 ? `Повторить через ${sms.cooldown.value}с` : 'Отправить код повторно' }}
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Step 3: Registration (new user) -->
                        <form v-else-if="sms.step.value === 'register'" @submit.prevent="submitRegister">
                            <p class="text-sm text-gray-600 mb-4">
                                Номер <span class="font-medium">{{ sms.phoneFormatted.value }}</span> не зарегистрирован. Заполните данные для создания аккаунта.
                            </p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Я хочу</label>
                                <div class="space-y-2">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            type="radio"
                                            name="role"
                                            value="athlete"
                                            v-model="registerForm.role"
                                            class="rounded-full border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                                        />
                                        <span class="ms-2 text-sm text-gray-700">Тренироваться (Атлет)</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            type="radio"
                                            name="role"
                                            value="coach"
                                            v-model="registerForm.role"
                                            class="rounded-full border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                                        />
                                        <span class="ms-2 text-sm text-gray-700">Проводить тренировки (Тренер)</span>
                                    </label>
                                </div>
                                <InputError class="mt-2" :message="sms.errors.value.role?.[0]" />
                            </div>

                            <div class="mt-4">
                                <Input
                                    id="sms_first_name"
                                    type="text"
                                    label="Имя"
                                    v-model="registerForm.first_name"
                                    :error="sms.errors.value.first_name?.[0]"
                                    required
                                    autofocus
                                    autocomplete="given-name"
                                />
                            </div>

                            <div class="mt-4">
                                <Input
                                    id="sms_last_name"
                                    type="text"
                                    label="Фамилия"
                                    v-model="registerForm.last_name"
                                    :error="sms.errors.value.last_name?.[0]"
                                    required
                                    autocomplete="family-name"
                                />
                            </div>

                            <div class="mt-6 space-y-3">
                                <Button
                                    type="submit"
                                    variant="primary"
                                    class="w-full"
                                    :disabled="sms.loading.value"
                                >
                                    {{ sms.loading.value ? 'Регистрация...' : 'Зарегистрироваться' }}
                                </Button>

                                <button
                                    type="button"
                                    @click="sms.goBack()"
                                    class="w-full text-sm text-gray-500 hover:text-gray-700 text-center"
                                >
                                    Назад
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Email Auth -->
                    <form v-else @submit.prevent="submit">
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
                                <span class="ms-2 text-sm text-gray-600">Запомнить меня</span>
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
