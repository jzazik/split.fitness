<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    error: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const model = defineModel({
    type: [String, Number],
    required: true,
});

const input = ref(null);

const inputClasses = computed(() => {
    const baseClasses = 'block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2';

    const errorClasses = props.error
        ? 'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500';

    const disabledClass = props.disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white';

    return `${baseClasses} ${errorClasses} ${disabledClass}`;
});

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({
    focus: () => input.value?.focus()
});
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="$attrs.id" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>

        <input
            ref="input"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :class="inputClasses"
            v-model="model"
            v-bind="$attrs"
        />

        <p v-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>
