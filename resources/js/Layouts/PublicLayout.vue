<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import UserAvatar from '@/Components/UI/UserAvatar.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';

defineProps({
    hideFooter: { type: Boolean, default: false },
    fullScreen: { type: Boolean, default: false },
});

const page = usePage();
const auth = computed(() => page.props.auth);
const user = computed(() => auth.value?.user);
const isHome = computed(() => page.url === '/' || page.url.startsWith('/?'));

const profileOpen = ref(false);
const menuVisible = ref(false);
const profileCardRef = ref(null);
const profileTriggerRef = ref(null);

const CLOSE_DURATION = 350;

const toggleProfile = async () => {
    if (profileOpen.value) {
        closeProfile();
    } else {
        profileOpen.value = true;
        await nextTick();
        requestAnimationFrame(() => { menuVisible.value = true; });
    }
};

const closeProfile = () => {
    if (!profileOpen.value) return;
    menuVisible.value = false;
    setTimeout(() => { profileOpen.value = false; }, CLOSE_DURATION);
};

const onOutsidePointerDown = (e) => {
    if (!profileOpen.value) return;
    const target = e.target;
    if (profileCardRef.value?.contains(target) || profileTriggerRef.value?.contains(target)) return;
    closeProfile();
};

const closeOnEscape = (e) => {
    if (profileOpen.value && e.key === 'Escape') closeProfile();
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('pointerdown', onOutsidePointerDown);
});
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('pointerdown', onOutsidePointerDown);
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
                    <div class="flex items-center gap-3">
                        <Link
                            v-if="!isHome"
                            :href="route('home')"
                            class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary-600 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM14 5.586v12.828l2.293 2.293A1 1 0 0018 20V10a1 1 0 00-.293-.707L14 5.586z" clip-rule="evenodd" />
                            </svg>
                            На карту
                        </Link>
                        <div v-if="user" class="relative">
                            <button
                                ref="profileTriggerRef"
                                type="button"
                                class="relative z-50 inline-flex items-center gap-2 rounded-full bg-white p-1 sm:pl-3 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition-colors"
                                @click="toggleProfile"
                            >
                                <span class="hidden sm:inline">{{ user.first_name }}</span>
                                <UserAvatar
                                    :first-name="user.first_name"
                                    :last-name="user.last_name"
                                    :avatar-url="auth.avatar_url"
                                    :sport-slug="auth.primary_sport_slug"
                                    :user-id="user.id"
                                    size="sm"
                                />
                            </button>

                            <div
                                v-if="profileOpen"
                                ref="profileCardRef"
                                class="profile-card absolute right-0 top-0 z-50 rounded-2xl bg-white shadow-lg ring-1 ring-gray-200 overflow-hidden"
                                :class="menuVisible ? 'profile-card--open' : 'profile-card--closed'"
                            >
                                <div class="flex items-center gap-2 p-1 sm:pl-3 text-sm font-medium text-gray-700">
                                    <span class="hidden sm:inline whitespace-nowrap">{{ user.first_name }}</span>
                                    <UserAvatar
                                        :first-name="user.first_name"
                                        :last-name="user.last_name"
                                        :avatar-url="auth.avatar_url"
                                        :sport-slug="auth.primary_sport_slug"
                                        :user-id="user.id"
                                        size="sm"
                                        class="ml-auto"
                                    />
                                </div>
                                <div class="profile-card__menu">
                                    <div class="py-1">
                                        <Link
                                            :href="route('profile.edit')"
                                            class="profile-card__item block whitespace-nowrap px-4 py-2 text-sm text-gray-700 text-end hover:bg-gray-50 transition-colors"
                                            @click="closeProfile"
                                        >
                                            Профиль
                                        </Link>
                                        <Link
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            class="profile-card__item block w-full whitespace-nowrap px-4 py-2 text-end text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                            @click="closeProfile"
                                        >
                                            Выйти
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Link
                            v-else
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

<style scoped>
.profile-card {
    transform-origin: top right;
    will-change: clip-path, transform;
    clip-path: circle(20px at calc(100% - 20px) 20px);
    transform: scale(1);
    transition:
        clip-path 0.3s cubic-bezier(0.55, 0, 0.1, 1),
        transform 0.25s cubic-bezier(0.55, 0, 0.1, 1);
}

.profile-card--open {
    clip-path: circle(150% at calc(100% - 20px) 20px);
    animation: card-bounce 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
    transition:
        clip-path 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.profile-card__menu {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.profile-card--open .profile-card__menu {
    opacity: 1;
    transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1) 0.15s;
}

.profile-card--open .profile-card__item:nth-child(1) {
    animation: item-slide 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
}

.profile-card--open .profile-card__item:nth-child(2) {
    animation: item-slide 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) 0.28s both;
}

@keyframes card-bounce {
    0%   { transform: scale(0.9); }
    45%  { transform: scale(1.045); }
    80%  { transform: scale(0.985); }
    100% { transform: scale(1); }
}

@keyframes item-slide {
    from {
        opacity: 0;
        transform: translateX(8px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>
