<template>
  <div class="hidden md:block absolute top-20 left-4 z-[1000] bg-white rounded-lg shadow-lg p-4 max-w-sm w-full">
    <!-- Sport Filter (Multiple Chips) -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Виды спорта
      </label>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="sport in sports"
          :key="sport.id"
          type="button"
          :class="[
            'px-3 py-1 rounded-full text-sm font-medium transition-colors',
            localFilters.sportIds.includes(sport.id)
              ? 'bg-primary-600 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
          ]"
          @click="toggleSport(sport.id)"
        >
          {{ sport.name }}
        </button>
      </div>
    </div>

    <!-- Date Filter -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Дата
      </label>
      <div class="flex flex-wrap gap-2 mb-2">
        <button
          v-for="preset in presets"
          :key="preset.value"
          type="button"
          :class="[
            'px-3 py-1 rounded-md text-sm font-medium transition-colors',
            selectedPreset === preset.value
              ? 'bg-primary-600 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
          ]"
          @click="selectDatePreset(preset.value)"
        >
          {{ preset.label }}
        </button>
      </div>

      <div v-if="selectedPreset === 'custom'" class="space-y-2">
        <input
          v-model="localFilters.dateFrom"
          type="date"
          :min="formatDate(new Date())"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
          placeholder="Дата от"
          @change="emitFilters"
        />
        <input
          v-model="localFilters.dateTo"
          type="date"
          :min="localFilters.dateFrom || formatDate(new Date())"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
          placeholder="Дата до"
          @change="emitFilters"
        />
      </div>
    </div>

    <!-- Reset Button -->
    <button
      type="button"
      class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors text-sm font-medium"
      @click="resetFilters"
    >
      Сбросить фильтры
    </button>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { useDatePresets } from '@/composables/useDatePresets';

const props = defineProps({
  sports: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: Object,
    default: () => ({
      sportIds: [],
      dateFrom: null,
      dateTo: null,
    }),
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const { presets, selectedPreset, computeRange, formatDate } = useDatePresets();

const localFilters = reactive({
  sportIds: [...(props.modelValue.sportIds || [])],
  dateFrom: props.modelValue.dateFrom,
  dateTo: props.modelValue.dateTo,
});

watch(() => props.modelValue, (newValue) => {
  localFilters.sportIds = [...(newValue.sportIds || [])];
  localFilters.dateFrom = newValue.dateFrom;
  localFilters.dateTo = newValue.dateTo;
}, { deep: true });

const toggleSport = (sportId) => {
  const index = localFilters.sportIds.indexOf(sportId);
  if (index > -1) {
    localFilters.sportIds.splice(index, 1);
  } else {
    localFilters.sportIds.push(sportId);
  }
  emitFilters();
};

const selectDatePreset = (preset) => {
  if (selectedPreset.value === preset) {
    selectedPreset.value = null;
    localFilters.dateFrom = null;
    localFilters.dateTo = null;
    emitFilters();
    return;
  }

  selectedPreset.value = preset;

  if (preset === 'custom') {
    localFilters.dateFrom = null;
    localFilters.dateTo = null;
    return;
  }

  const range = computeRange(preset);
  if (range) {
    localFilters.dateFrom = range.dateFrom;
    localFilters.dateTo = range.dateTo;
  }

  emitFilters();
};

const resetFilters = () => {
  localFilters.sportIds = [];
  localFilters.dateFrom = null;
  localFilters.dateTo = null;
  selectedPreset.value = null;
  emitFilters();
};

const emitFilters = () => {
  const payload = {
    sportIds: [...localFilters.sportIds],
    dateFrom: localFilters.dateFrom,
    dateTo: localFilters.dateTo,
  };
  emit('update:modelValue', payload);
  emit('change', payload);
};
</script>
