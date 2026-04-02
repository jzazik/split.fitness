<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    error: { type: String, default: null },
    label: { type: String, default: null },
    placeholder: { type: String, default: '900 123-45-67' },
    disabled: { type: Boolean, default: false },
    hint: { type: String, default: null },
});

const model = defineModel({ type: String, required: true });
const input = ref(null);

function formatDigits(digits) {
    if (digits.length <= 3) return digits;
    if (digits.length <= 6) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
    if (digits.length <= 8) return `${digits.slice(0, 3)} ${digits.slice(3, 6)}-${digits.slice(6)}`;
    return `${digits.slice(0, 3)} ${digits.slice(3, 6)}-${digits.slice(6, 8)}-${digits.slice(8, 10)}`;
}

function onInput() {
    const digits = model.value.replace(/\D/g, '').slice(0, 10);
    model.value = formatDigits(digits);
}

const inputClasses = computed(() => {
    const base = 'w-full rounded-lg border pl-9 pr-3 py-2.5 text-sm transition-colors focus:outline-none focus:ring-2';
    const state = props.error
        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500';
    const disabled = props.disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white';
    return `${base} ${state} ${disabled}`;
});

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="$attrs.id" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ label }}
        </label>

        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-sm pointer-events-none">+7</span>
            <input
                ref="input"
                v-model="model"
                type="tel"
                inputmode="numeric"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="inputClasses"
                maxlength="13"
                v-bind="$attrs"
                @input="onInput"
            />
        </div>

        <p v-if="error" class="mt-1.5 text-xs text-red-500">{{ error }}</p>
        <p v-else-if="hint" class="mt-1.5 text-xs text-gray-400">{{ hint }}</p>
    </div>
</template>
