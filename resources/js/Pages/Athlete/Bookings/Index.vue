<script setup>
import AthleteLayout from '@/Layouts/AthleteLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AthleteLayout>
        <Head title="Мои тренировки" />

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
                            У вас пока нет записей на тренировки. Здесь появятся ваши будущие тренировки.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AthleteLayout>
</template>
