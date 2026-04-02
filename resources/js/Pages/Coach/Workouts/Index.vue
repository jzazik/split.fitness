<script setup>
import CoachLayout from '@/Layouts/CoachLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Button from '@/Components/UI/Button.vue';
import { formatPrice } from '@/utils/workout';

const props = defineProps({
    workouts: Object,
    filters: Object,
    coachModerationStatus: String,
});

const selectedStatus = ref(props.filters?.status || '');

const filterByStatus = (status) => {
    selectedStatus.value = status;
    router.get(route('coach.workouts.index'), { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusBadgeClass = (status) => {
    const baseClasses = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';

    switch (status) {
        case 'draft':
            return `${baseClasses} bg-gray-100 text-gray-800`;
        case 'published':
            return `${baseClasses} bg-green-100 text-green-800`;
        case 'cancelled':
            return `${baseClasses} bg-red-100 text-red-800`;
        case 'completed':
            return `${baseClasses} bg-blue-100 text-blue-800`;
        default:
            return `${baseClasses} bg-gray-100 text-gray-800`;
    }
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Черновик',
        published: 'Опубликована',
        cancelled: 'Отменена',
        completed: 'Завершена',
    };
    return labels[status] || status;
};

// Format date/time consistently for SSR and client to avoid hydration mismatch
const formatDate = (dateString) => {
    const date = new Date(dateString);
    // Display in browser's local timezone (or UTC during SSR) using Intl.DateTimeFormat
    // This ensures consistent rendering between server and client
    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: typeof window === 'undefined' ? 'UTC' : undefined,
    }).format(date);
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    // Display in browser's local timezone (or UTC during SSR) using Intl.DateTimeFormat
    // This ensures consistent rendering between server and client
    return new Intl.DateTimeFormat('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: typeof window === 'undefined' ? 'UTC' : undefined,
    }).format(date);
};

const canPublish = computed(() => {
    return props.coachModerationStatus === 'approved';
});

const publishWorkout = (workoutId) => {
    if (!canPublish.value) {
        return;
    }

    if (confirm('Вы уверены, что хотите опубликовать эту тренировку?')) {
        router.post(route('coach.workouts.publish', workoutId), {}, {
            preserveScroll: true,
        });
    }
};

const cancelWorkout = (workoutId) => {
    if (confirm('Вы уверены, что хотите отменить эту тренировку? Это действие нельзя отменить.')) {
        router.post(route('coach.workouts.cancel', workoutId), {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <CoachLayout>
        <Head title="Мои тренировки" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Мои тренировки</h1>
                        <p class="mt-1 text-sm text-gray-600">
                            Управляйте вашими тренировками
                        </p>
                    </div>
                    <Link :href="route('coach.workouts.create')">
                        <Button class="bg-primary-600 text-white hover:bg-primary-700">
                            Создать тренировку
                        </Button>
                    </Link>
                </div>

                <!-- Status Filters -->
                <div class="mb-4 flex items-center gap-2">
                    <span class="text-sm text-gray-600">Фильтр по статусу:</span>
                    <button
                        @click="filterByStatus('')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium transition',
                            selectedStatus === ''
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Все
                    </button>
                    <button
                        @click="filterByStatus('draft')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium transition',
                            selectedStatus === 'draft'
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Черновики
                    </button>
                    <button
                        @click="filterByStatus('published')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium transition',
                            selectedStatus === 'published'
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Опубликованные
                    </button>
                    <button
                        @click="filterByStatus('cancelled')"
                        :class="[
                            'px-3 py-1 rounded-md text-sm font-medium transition',
                            selectedStatus === 'cancelled'
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        Отменённые
                    </button>
                </div>

                <!-- Workouts Table -->
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div v-if="workouts.data.length === 0" class="p-12 text-center">
                        <p class="text-gray-500">У вас пока нет тренировок</p>
                        <Link :href="route('coach.workouts.create')" class="mt-4 inline-block">
                            <Button class="bg-primary-600 text-white hover:bg-primary-700">
                                Создать первую тренировку
                            </Button>
                        </Link>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        #ID
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Дата
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Время
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Спорт
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Место
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Цена
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Места
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Статус
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Действия
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="workout in workouts.data" :key="workout.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        #{{ workout.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(workout.starts_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatTime(workout.starts_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ workout.sport?.name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-xs">
                                            <div class="font-medium">{{ workout.city?.name }}</div>
                                            <div class="text-gray-500 text-xs truncate">{{ workout.location_name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatPrice(workout.slot_price) }} ₽
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ workout.slots_booked }} / {{ workout.slots_total }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadgeClass(workout.status)">
                                            {{ getStatusLabel(workout.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <Link
                                            v-if="['draft', 'published'].includes(workout.status)"
                                            :href="route('coach.workouts.edit', workout.id)"
                                            class="text-primary-600 hover:text-primary-900"
                                        >
                                            Редактировать
                                        </Link>
                                        <button
                                            v-if="workout.status === 'draft'"
                                            @click="publishWorkout(workout.id)"
                                            :disabled="!canPublish"
                                            :title="!canPublish ? 'Дождитесь модерации профиля' : ''"
                                            :class="[
                                                canPublish
                                                    ? 'text-green-600 hover:text-green-900 cursor-pointer'
                                                    : 'text-gray-400 cursor-not-allowed'
                                            ]"
                                        >
                                            Опубликовать
                                        </button>
                                        <button
                                            v-if="workout.status === 'published'"
                                            @click="cancelWorkout(workout.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Отменить
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="workouts.links && workouts.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link
                                    v-if="workouts.prev_page_url"
                                    :href="workouts.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Назад
                                </Link>
                                <Link
                                    v-if="workouts.next_page_url"
                                    :href="workouts.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Вперёд
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Показано
                                        <span class="font-medium">{{ workouts.from || 0 }}</span>
                                        -
                                        <span class="font-medium">{{ workouts.to || 0 }}</span>
                                        из
                                        <span class="font-medium">{{ workouts.total }}</span>
                                        результатов
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <Link
                                            v-for="(link, index) in workouts.links"
                                            :key="index"
                                            :href="link.url"
                                            :class="[
                                                link.active
                                                    ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                index === 0 ? 'rounded-l-md' : '',
                                                index === workouts.links.length - 1 ? 'rounded-r-md' : '',
                                            ]"
                                        >
                                            {{ link.label }}
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CoachLayout>
</template>
