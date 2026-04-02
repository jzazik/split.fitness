<script setup>
import AthleteLayout from '@/Layouts/AthleteLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { formatPrice } from '@/utils/workout';
import { ref, computed } from 'vue';

const props = defineProps({
    upcoming: Array,
    past: Array,
    cancelled: Array,
});

const activeTab = ref('upcoming');

const currentBookings = computed(() => {
    switch (activeTab.value) {
        case 'upcoming':
            return props.upcoming;
        case 'past':
            return props.past;
        case 'cancelled':
            return props.cancelled;
        default:
            return [];
    }
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: typeof window === 'undefined' ? 'UTC' : undefined,
    }).format(date);
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: typeof window === 'undefined' ? 'UTC' : undefined,
    }).format(date);
};

const getStatusBadgeClass = (status) => {
    const baseClasses = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';

    switch (status) {
        case 'pending_payment':
            return `${baseClasses} bg-yellow-100 text-yellow-800`;
        case 'paid':
            return `${baseClasses} bg-green-100 text-green-800`;
        case 'expired':
            return `${baseClasses} bg-gray-100 text-gray-800`;
        case 'cancelled':
            return `${baseClasses} bg-red-100 text-red-800`;
        case 'refunded':
            return `${baseClasses} bg-blue-100 text-blue-800`;
        default:
            return `${baseClasses} bg-gray-100 text-gray-800`;
    }
};

const getStatusLabel = (status) => {
    const labels = {
        pending_payment: 'Ожидает оплаты',
        paid: 'Оплачено',
        expired: 'Истекло',
        cancelled: 'Отменено',
        refunded: 'Возврат',
    };
    return labels[status] || status;
};

const canCancelBooking = (booking) => {
    const now = new Date();
    const workoutStart = new Date(booking.workout.starts_at);
    const hoursUntilStart = (workoutStart - now) / (1000 * 60 * 60);

    return booking.status === 'paid' && hoursUntilStart > 24;
};

const cancelBooking = (bookingId) => {
    if (confirm('Вы уверены, что хотите отменить эту запись?')) {
        console.log('Cancel booking:', bookingId);
    }
};
</script>

<template>
    <AthleteLayout>
        <Head title="Мои тренировки" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Мои тренировки</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Управляйте вашими записями на тренировки
                    </p>
                </div>

                <div class="mb-4 flex items-center gap-2 border-b border-gray-200">
                    <button
                        @click="activeTab = 'upcoming'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition border-b-2',
                            activeTab === 'upcoming'
                                ? 'border-primary-600 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Ближайшие
                        <span v-if="upcoming.length > 0" class="ml-1 text-xs">
                            ({{ upcoming.length }})
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'past'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition border-b-2',
                            activeTab === 'past'
                                ? 'border-primary-600 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Прошедшие
                        <span v-if="past.length > 0" class="ml-1 text-xs">
                            ({{ past.length }})
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'cancelled'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition border-b-2',
                            activeTab === 'cancelled'
                                ? 'border-primary-600 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        Отменённые
                        <span v-if="cancelled.length > 0" class="ml-1 text-xs">
                            ({{ cancelled.length }})
                        </span>
                    </button>
                </div>

                <div v-if="currentBookings.length === 0" class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                    <p class="text-gray-500">
                        <template v-if="activeTab === 'upcoming'">
                            У вас пока нет предстоящих тренировок
                        </template>
                        <template v-else-if="activeTab === 'past'">
                            У вас пока нет прошедших тренировок
                        </template>
                        <template v-else>
                            У вас нет отменённых тренировок
                        </template>
                    </p>
                    <Link :href="route('map')" class="mt-4 inline-block text-primary-600 hover:text-primary-800">
                        Найти тренировку
                    </Link>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="booking in currentBookings"
                        :key="booking.id"
                        :href="route('athlete.bookings.show', booking.id)"
                        class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200 hover:border-primary-300 transition cursor-pointer block"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ booking.workout.sport?.name || 'Тренировка' }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ booking.workout.title || '' }}
                                    </p>
                                </div>
                                <span :class="getStatusBadgeClass(booking.status)">
                                    {{ getStatusLabel(booking.status) }}
                                </span>
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex items-center text-gray-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ formatDate(booking.workout.starts_at) }} в {{ formatTime(booking.workout.starts_at) }}
                                </div>

                                <div class="flex items-center text-gray-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ booking.workout.city?.name }}, {{ booking.workout.location_name }}
                                </div>

                                <div class="flex items-center text-gray-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ booking.workout.coach?.first_name }} {{ booking.workout.coach?.last_name }}
                                </div>

                                <div class="flex items-center font-semibold text-gray-900 pt-2 border-t">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ formatPrice(booking.total_amount) }} ₽
                                </div>
                            </div>

                            <div v-if="canCancelBooking(booking)" class="mt-4 pt-4 border-t">
                                <button
                                    @click.prevent="cancelBooking(booking.id)"
                                    class="w-full text-center text-sm text-red-600 hover:text-red-800 font-medium"
                                >
                                    Отменить запись
                                </button>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AthleteLayout>
</template>
