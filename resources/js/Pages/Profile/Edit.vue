<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AvatarUploader from '@/Components/UI/AvatarUploader.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { getSportIconPaths } from '@/utils/sportIcons';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    avatarUrl: String,
    sports: Array,
});

const user = usePage().props.auth.user;

const form = useForm({
    first_name: user.first_name,
    last_name: user.last_name,
    phone: user.phone || '',
    email: user.email,
});

const selectedSportIds = ref([]);

const handleAvatarUpload = (file) => {
    const data = new FormData();
    data.append('avatar', file);

    router.post(route('profile.uploadAvatar'), data, {
        preserveScroll: true,
    });
};

const handleAvatarRemove = () => {
    router.delete(route('profile.deleteAvatar'), {
        preserveScroll: true,
    });
};

const toggleSport = (sportId) => {
    const idx = selectedSportIds.value.indexOf(sportId);
    if (idx > -1) {
        selectedSportIds.value.splice(idx, 1);
    } else {
        selectedSportIds.value.push(sportId);
    }
};

const goToMapWithSports = () => {
    const params = {};
    if (selectedSportIds.value.length) {
        params.sportIds = selectedSportIds.value.join(',');
    }
    router.get(route('home'), params);
};
</script>

<template>
    <Head title="Профиль" />

    <div class="min-h-screen bg-gray-100">
        <!-- Navbar: logo + "На карту" -->
        <nav class="border-b border-gray-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link :href="route('home')">
                        <ApplicationLogo />
                    </Link>

                    <Link
                        :href="route('home')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM14 5.586v12.828l2.293 2.293A1 1 0 0018 20V10a1 1 0 00-.293-.707L14 5.586z" clip-rule="evenodd" />
                        </svg>
                        На карту
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- Avatar + Form Card -->
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="px-6 pt-6 pb-4">
                        <AvatarUploader
                            :current-url="avatarUrl"
                            :max-size-mb="5"
                            @upload="handleAvatarUpload"
                            @remove="handleAvatarRemove"
                        />
                        <InputError class="mt-2" :message="$page.props.errors?.avatar" />
                    </div>

                    <div class="border-t border-gray-100 px-6 py-5">
                        <form
                            @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })"
                            class="space-y-4"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">
                                        Имя <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="first_name"
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        autocomplete="given-name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    />
                                    <InputError class="mt-1" :message="form.errors.first_name" />
                                </div>

                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">
                                        Фамилия <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="last_name"
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        autocomplete="family-name"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    />
                                    <InputError class="mt-1" :message="form.errors.last_name" />
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">
                                    Телефон
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    autocomplete="tel"
                                    placeholder="+7 (___) ___-__-__"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                />
                                <InputError class="mt-1" :message="form.errors.phone" />
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Эл. почта <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    autocomplete="username"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                />
                                <InputError class="mt-1" :message="form.errors.email" />
                            </div>

                            <div
                                v-if="mustVerifyEmail && user.email_verified_at === null"
                                class="rounded-md bg-yellow-50 p-3"
                            >
                                <p class="text-sm text-yellow-800">
                                    Ваш адрес электронной почты не подтверждён.
                                    <Link
                                        :href="route('verification.send')"
                                        method="post"
                                        as="button"
                                        class="font-medium text-yellow-700 underline hover:text-yellow-900"
                                    >
                                        Отправить письмо повторно.
                                    </Link>
                                </p>
                                <p
                                    v-if="status === 'verification-link-sent'"
                                    class="mt-1 text-sm font-medium text-green-600"
                                >
                                    Новая ссылка для подтверждения отправлена на вашу почту.
                                </p>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Сохранение...' : 'Сохранить' }}
                                </button>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600">
                                        Сохранено.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sport Filters -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-5">
                        <h3 class="text-lg font-medium text-gray-900">
                            Виды спорта
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Выберите интересующие виды спорта и перейдите к поиску тренировок на карте.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                v-for="sport in sports"
                                :key="sport.id"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors',
                                    selectedSportIds.includes(sport.id)
                                        ? 'bg-primary-600 text-white shadow-sm'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                                @click="toggleSport(sport.id)"
                            >
                                <svg
                                    class="size-4 shrink-0"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    v-html="getSportIconPaths(sport.slug)"
                                />
                                {{ sport.name }}
                            </button>
                        </div>

                        <button
                            v-if="selectedSportIds.length"
                            type="button"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                            @click="goToMapWithSports"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                            Найти тренировки на карте
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
