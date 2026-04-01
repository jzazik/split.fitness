<script setup>
import { computed } from 'vue';
import CoachLayout from '@/Layouts/CoachLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const coachProfile = computed(() => page.props.auth?.coachProfile || null);

const handleResubmit = () => {
    router.post(route('coach.profile.resubmit'));
};
</script>

<template>
    <CoachLayout>
        <Head title="Тренировки" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Мои тренировки
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Moderation Status Messages -->
                <div v-if="coachProfile" class="mb-6">
                    <!-- Pending Status -->
                    <div
                        v-if="coachProfile.moderation_status === 'pending'"
                        class="rounded-lg border border-yellow-200 bg-yellow-50 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-yellow-400"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Ваш профиль на проверке
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>
                                        Вы сможете публиковать тренировки после одобрения
                                        администратором. Обычно проверка занимает 1-2 рабочих дня.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rejected Status -->
                    <div
                        v-if="coachProfile.moderation_status === 'rejected'"
                        class="rounded-lg border border-red-200 bg-red-50 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-red-400"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-red-800">
                                    Ваш профиль отклонён
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p v-if="coachProfile.rejection_reason">
                                        Причина: {{ coachProfile.rejection_reason }}
                                    </p>
                                    <p class="mt-1">
                                        Пожалуйста, обновите информацию в профиле и отправьте на
                                        повторную проверку.
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <button
                                        @click="handleResubmit"
                                        type="button"
                                        class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                                    >
                                        Отправить на повторную модерацию
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-center text-gray-500">
                            У вас пока нет запланированных тренировок. Здесь появятся ваши расписания и записи клиентов.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </CoachLayout>
</template>
