<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AdminLayout>
        <Head title="Админ панель" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Админ панель
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Админ панель (в разработке)
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
