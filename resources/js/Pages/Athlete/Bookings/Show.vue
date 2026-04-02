<script setup>
import AthleteLayout from '@/Layouts/AthleteLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    booking: Object,
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

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: typeof window === 'undefined' ? 'UTC' : undefined,
    }).format(date);
};

const getStatusBadgeClass = (status) => {
    const baseClasses = 'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium';

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

const getPaymentStatusLabel = (paymentStatus) => {
    const labels = {
        pending: 'Ожидает оплаты',
        paid: 'Оплачено',
        failed: 'Ошибка оплаты',
        refunded: 'Возврат',
    };
    return labels[paymentStatus] || paymentStatus;
};

const canCancelBooking = () => {
    const now = new Date();
    const workoutStart = new Date(props.booking.workout.starts_at);
    const hoursUntilStart = (workoutStart - now) / (1000 * 60 * 60);

    return props.booking.status === 'paid' && hoursUntilStart > 24;
};

const cancelBooking = () => {
    if (confirm('Вы уверены, что хотите отменить эту запись? Возврат средств будет произведён на банковскую карту в течение 5-7 рабочих дней.')) {
        // TODO: implement cancel booking endpoint in next sprint
        console.log('Cancel booking:', props.booking.id);
        alert('Функция отмены бронирования будет реализована в следующем спринте');
    }
};
</script>

<template>
    <AthleteLayout>
        <Head title="Детали записи" />

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link
                        :href="route('athlete.bookings')"
                        class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Назад к списку
                    </Link>

                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Детали записи</h1>
                            <p class="mt-1 text-sm text-gray-600">
                                Бронирование #{{ booking.id }}
                            </p>
                        </div>
                        <span :class="getStatusBadgeClass(booking.status)">
                            {{ getStatusLabel(booking.status) }}
                        </span>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <!-- Workout Details -->
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Детали тренировки</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Вид спорта</label>
                                <p class="text-base text-gray-900">
                                    {{ booking.workout.sport?.name || 'Не указан' }}
                                </p>
                            </div>

                            <div v-if="booking.workout.title">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Название тренировки</label>
                                <p class="text-base text-gray-900">
                                    {{ booking.workout.title }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Дата и время</label>
                                <div class="flex items-center text-base text-gray-900">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ formatDate(booking.workout.starts_at) }} в {{ formatTime(booking.workout.starts_at) }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Продолжительность</label>
                                <p class="text-base text-gray-900">
                                    {{ booking.workout.duration_minutes }} минут
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Место проведения</label>
                                <div class="flex items-start text-base text-gray-900">
                                    <svg class="w-5 h-5 mr-2 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <div>
                                        <p>{{ booking.workout.location_name }}</p>
                                        <p class="text-sm text-gray-600">{{ booking.workout.city?.name }}</p>
                                        <p v-if="booking.workout.address" class="text-sm text-gray-600">
                                            {{ booking.workout.address }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Тренер</label>
                                <div class="flex items-center text-base text-gray-900">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ booking.workout.coach?.first_name }} {{ booking.workout.coach?.last_name }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Детали бронирования</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Дата создания</label>
                                <p class="text-base text-gray-900">
                                    {{ formatDateTime(booking.created_at) }}
                                </p>
                            </div>

                            <div v-if="booking.booked_at">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Дата подтверждения</label>
                                <p class="text-base text-gray-900">
                                    {{ formatDateTime(booking.booked_at) }}
                                </p>
                            </div>

                            <div v-if="booking.cancelled_at">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Дата отмены</label>
                                <p class="text-base text-gray-900">
                                    {{ formatDateTime(booking.cancelled_at) }}
                                </p>
                            </div>

                            <div v-if="booking.cancellation_reason">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Причина отмены</label>
                                <p class="text-base text-gray-900">
                                    {{ booking.cancellation_reason }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Количество мест</label>
                                <p class="text-base text-gray-900">
                                    {{ booking.slots_count }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Оплата</h2>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Статус оплаты</label>
                                <p class="text-base text-gray-900">
                                    {{ getPaymentStatusLabel(booking.payment_status) }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Стоимость за место</label>
                                <p class="text-base text-gray-900">
                                    {{ booking.slot_price }} ₽
                                </p>
                            </div>

                            <div class="pt-2 border-t">
                                <label class="block text-sm font-medium text-gray-500 mb-1">Итого к оплате</label>
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ booking.total_amount }} ₽
                                </p>
                            </div>

                            <div v-if="booking.payment_status === 'paid'" class="pt-4 border-t">
                                <div class="flex items-center text-green-600">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium">Оплата получена</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">
                                    Электронный чек отправлен на вашу электронную почту
                                </p>
                            </div>

                            <div v-if="booking.payment_status === 'pending'" class="pt-4 border-t">
                                <div class="flex items-center text-yellow-600">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="font-medium">Ожидается оплата</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-600">
                                    Бронирование будет автоматически отменено через 15 минут, если оплата не поступит
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div v-if="canCancelBooking()" class="p-6 border-t bg-gray-50">
                        <button
                            @click="cancelBooking"
                            class="w-full sm:w-auto px-6 py-2 bg-white border border-red-300 text-red-700 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 font-medium"
                        >
                            Отменить запись
                        </button>
                        <p class="mt-2 text-sm text-gray-600">
                            Отмена возможна не позднее чем за 24 часа до начала тренировки. Возврат средств осуществляется в течение 5-7 рабочих дней.
                        </p>
                    </div>

                    <div v-else-if="booking.status === 'pending_payment'" class="p-6 border-t bg-gray-50">
                        <p class="text-sm text-gray-600">
                            Для завершения бронирования необходимо произвести оплату. После оплаты вы получите подтверждение на электронную почту.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AthleteLayout>
</template>
