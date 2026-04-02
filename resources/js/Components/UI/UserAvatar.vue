<script setup>
import { computed, ref } from 'vue';
import { getSportIconSvg, getRandomSportIconSvg } from '@/utils/sportIcons';

const stableRandomIcon = ref(getRandomSportIconSvg());

const props = defineProps({
    firstName: { type: String, default: '' },
    lastName: { type: String, default: '' },
    avatarUrl: { type: String, default: null },
    sportSlug: { type: String, default: null },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
});

const hasName = computed(() => {
    return !!(props.firstName?.trim() || props.lastName?.trim());
});

const initials = computed(() => {
    const f = props.firstName?.trim()?.[0] ?? '';
    const l = props.lastName?.trim()?.[0] ?? '';
    return (f + l).toUpperCase();
});

const fallbackIconHtml = computed(() => {
    if (props.sportSlug) return getSportIconSvg(props.sportSlug);
    return stableRandomIcon.value;
});

const sportBadgeHtml = computed(() => {
    if (!hasName.value || !props.sportSlug) return null;
    return getSportIconSvg(props.sportSlug);
});

const sizeClasses = computed(() => ({
    sm: { circle: 'size-8 text-xs', badge: 'size-4 -bottom-0.5 -right-0.5' },
    md: { circle: 'size-10 text-sm', badge: 'size-5 -bottom-0.5 -right-0.5' },
    lg: { circle: 'size-14 text-base', badge: 'size-6 -bottom-1 -right-1' },
}[props.size]));
</script>

<template>
    <div class="relative inline-flex shrink-0">
        <div
            :class="[
                sizeClasses.circle,
                'rounded-full flex items-center justify-center font-semibold overflow-hidden ring-2 ring-white',
            ]"
        >
            <img
                v-if="avatarUrl"
                :src="avatarUrl"
                :alt="initials || 'avatar'"
                class="size-full object-cover"
            />
            <span
                v-else-if="hasName"
                class="size-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700 text-white"
            >
                {{ initials }}
            </span>
            <span
                v-else
                class="size-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700 text-white p-1.5"
                v-html="fallbackIconHtml"
            />
        </div>

        <span
            v-if="sportBadgeHtml"
            :class="[
                sizeClasses.badge,
                'absolute rounded-full bg-white ring-1 ring-gray-200 flex items-center justify-center text-primary-600 shadow-sm',
            ]"
            v-html="sportBadgeHtml"
        />
    </div>
</template>
