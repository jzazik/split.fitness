<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'button',
    },
});

const variantClasses = computed(() => {
    const baseClasses = 'inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2';

    const variants = {
        primary: 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 active:bg-primary-800',
        secondary: 'bg-secondary-200 text-secondary-900 hover:bg-secondary-300 focus:ring-secondary-500 active:bg-secondary-400',
        danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 active:bg-red-800',
    };

    const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : '';

    return `${baseClasses} ${variants[props.variant]} ${disabledClass}`;
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        :class="variantClasses"
    >
        <slot />
    </button>
</template>
