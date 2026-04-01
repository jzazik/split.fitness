<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    options: {
        type: Array,
        required: true,
    },
    label: {
        type: String,
        default: 'Выберите...',
    },
    placeholder: {
        type: String,
        default: 'Поиск...',
    },
    valueKey: {
        type: String,
        default: 'id',
    },
    labelKey: {
        type: String,
        default: 'name',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');

const filteredOptions = computed(() => {
    if (!searchQuery.value) {
        return props.options;
    }

    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => {
        const label = option[props.labelKey]?.toLowerCase() || '';
        return label.includes(query);
    });
});

const selectedItems = computed(() => {
    return props.options.filter(option =>
        props.modelValue.includes(option[props.valueKey])
    );
});

const selectedCount = computed(() => props.modelValue.length);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
    }
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

const toggleOption = (optionValue) => {
    const index = props.modelValue.indexOf(optionValue);
    const newValue = [...props.modelValue];

    if (index > -1) {
        newValue.splice(index, 1);
    } else {
        newValue.push(optionValue);
    }

    emit('update:modelValue', newValue);
};

const removeBadge = (optionValue) => {
    const index = props.modelValue.indexOf(optionValue);
    if (index > -1) {
        const newValue = [...props.modelValue];
        newValue.splice(index, 1);
        emit('update:modelValue', newValue);
    }
};

const isSelected = (optionValue) => {
    return props.modelValue.includes(optionValue);
};
</script>

<template>
    <div class="relative">
        <!-- Selected Badges -->
        <div v-if="selectedCount > 0" class="mb-2 flex flex-wrap gap-2">
            <span
                v-for="item in selectedItems"
                :key="item[valueKey]"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
            >
                {{ item[labelKey] }}
                <button
                    type="button"
                    @click.stop="removeBadge(item[valueKey])"
                    class="ml-1.5 inline-flex items-center justify-center w-4 h-4 text-blue-600 hover:bg-blue-200 hover:text-blue-900 rounded-full transition"
                >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </span>
        </div>

        <!-- Dropdown Toggle -->
        <button
            type="button"
            @click="toggleDropdown"
            class="w-full px-4 py-2 text-left border border-gray-300 rounded-md bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
        >
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700">
                    {{ selectedCount > 0 ? `Выбрано: ${selectedCount}` : label }}
                </span>
                <svg
                    class="w-5 h-5 text-gray-400 transition-transform"
                    :class="{ 'rotate-180': isOpen }"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </div>
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg"
        >
            <!-- Search Input -->
            <div class="p-2 border-b border-gray-200">
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="placeholder"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    @click.stop
                >
            </div>

            <!-- Options List -->
            <div class="max-h-60 overflow-y-auto">
                <label
                    v-for="option in filteredOptions"
                    :key="option[valueKey]"
                    class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer transition"
                    @click.stop
                >
                    <input
                        type="checkbox"
                        :value="option[valueKey]"
                        :checked="isSelected(option[valueKey])"
                        @change="toggleOption(option[valueKey])"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    >
                    <span class="ml-2 text-sm text-gray-700">
                        {{ option[labelKey] }}
                    </span>
                </label>

                <!-- No Results -->
                <div
                    v-if="filteredOptions.length === 0"
                    class="px-3 py-4 text-sm text-gray-500 text-center"
                >
                    Ничего не найдено
                </div>
            </div>

            <!-- Close Button -->
            <div class="p-2 border-t border-gray-200">
                <button
                    type="button"
                    @click="closeDropdown"
                    class="w-full px-3 py-1.5 text-sm text-gray-700 bg-gray-100 rounded hover:bg-gray-200 transition"
                >
                    Закрыть
                </button>
            </div>
        </div>

        <!-- Overlay to close dropdown when clicking outside -->
        <div
            v-if="isOpen"
            @click="closeDropdown"
            class="fixed inset-0 z-0"
        />
    </div>
</template>
