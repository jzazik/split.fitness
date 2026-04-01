<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    cities: {
        type: Array,
        default: () => [],
    },
});

const selectedCityId = ref(null);

const findWorkouts = () => {
    if (selectedCityId.value) {
        router.visit(`/map?city_id=${selectedCityId.value}`);
    } else {
        router.visit('/map');
    }
};

const autoDetectLocation = () => {
    // Future enhancement: use geolocation API
    // For now, just go to map without city filter
    router.visit('/map');
};
</script>

<template>
    <Head title="Найди тренировку рядом с тобой" />

    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <span class="text-2xl font-bold text-indigo-600">Split.Fitness</span>
                    </div>
                    <div v-if="canLogin" class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
                            >
                                Войти
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium"
                            >
                                Регистрация
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="flex items-center justify-center px-4 sm:px-6 lg:px-8 py-20">
            <div class="max-w-2xl w-full text-center">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">
                    Найди тренировку рядом с тобой
                </h1>
                <p class="text-xl text-gray-600 mb-12">
                    Присоединяйся к тренировкам с лучшими тренерами в твоем городе
                </p>

                <!-- City Selection Card -->
                <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">
                    <div>
                        <label for="city-select" class="block text-left text-sm font-medium text-gray-700 mb-2">
                            Выбери свой город
                        </label>
                        <select
                            id="city-select"
                            v-model="selectedCityId"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option :value="null">Все города</option>
                            <option
                                v-for="city in cities"
                                :key="city.id"
                                :value="city.id"
                            >
                                {{ city.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button
                            @click="findWorkouts"
                            class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition-colors"
                        >
                            Найти тренировки
                        </button>
                        <button
                            @click="autoDetectLocation"
                            class="flex-1 bg-white border-2 border-indigo-600 text-indigo-600 px-6 py-3 rounded-lg text-lg font-semibold hover:bg-indigo-50 transition-colors"
                        >
                            Определить местоположение
                        </button>
                    </div>
                </div>

                <!-- Features -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Удобный поиск</h3>
                        <p class="text-gray-600">Находи тренировки на карте рядом с тобой</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Лучшие тренеры</h3>
                        <p class="text-gray-600">Профессионалы с подтвержденной квалификацией</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-indigo-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Гибкое расписание</h3>
                        <p class="text-gray-600">Бронируй тренировки в удобное время</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
