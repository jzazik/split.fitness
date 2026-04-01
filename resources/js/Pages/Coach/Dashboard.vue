<script setup>
import CoachLayout from '@/Layouts/CoachLayout.vue';
import { Head } from '@inertiajs/vue3';
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
