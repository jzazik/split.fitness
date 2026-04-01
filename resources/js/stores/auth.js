import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export const useAuthStore = defineStore('auth', () => {
    // State - get user from Inertia shared props
    const page = usePage();
    const user = computed(() => page.props.auth?.user || null);

    // Getters
    const isAuthenticated = computed(() => user.value !== null);

    const isAthlete = computed(() => user.value?.role === 'athlete');

    const isCoach = computed(() => user.value?.role === 'coach');

    const isAdmin = computed(() => user.value?.role === 'admin');

    const fullName = computed(() => user.value?.full_name || '');

    // Actions
    const logout = () => {
        router.post(route('logout'));
    };

    return {
        user,
        isAuthenticated,
        isAthlete,
        isCoach,
        isAdmin,
        fullName,
        logout,
    };
});
