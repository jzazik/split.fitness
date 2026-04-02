<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    hideFooter: { type: Boolean, default: false },
    fullScreen: { type: Boolean, default: false },
});
</script>

<template>
    <div :class="fullScreen ? 'h-dvh relative overflow-hidden bg-gray-50' : 'min-h-screen flex flex-col bg-gray-50'">
        <!-- Header -->
        <header :class="fullScreen ? 'absolute top-0 inset-x-0 z-[1100] bg-white/80 backdrop-blur-sm' : 'bg-white border-b border-gray-200'">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center">
                            <ApplicationLogo />
                        </Link>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex items-center">
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition"
                        >
                            Войти
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main :class="fullScreen ? 'h-full' : 'flex-grow'">
            <slot />
        </main>

        <!-- Footer -->
        <footer v-if="!hideFooter" class="bg-white border-t border-gray-200 mt-auto">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    &copy; 2026 split.fitness. Все права защищены.
                </p>
            </div>
        </footer>
    </div>
</template>
