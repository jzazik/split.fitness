<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col bg-gray-50">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center">
                            <ApplicationLogo class="h-8 w-auto fill-current text-gray-800" />
                            <span class="ml-2 text-xl font-semibold text-gray-900">split.fitness</span>
                        </Link>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('login')"
                            class="text-sm font-medium text-gray-700 hover:text-gray-900 transition"
                        >
                            Войти
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition"
                        >
                            Регистрация
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    &copy; 2026 split.fitness. Все права защищены.
                </p>
            </div>
        </footer>
    </div>
</template>
