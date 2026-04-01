<template>
  <div class="absolute top-4 left-4 z-[1000] bg-white rounded-lg shadow-lg p-4 max-w-sm w-full">
    <!-- City Filter -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Город
      </label>
      <select
        v-model="localFilters.cityId"
        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        @change="emitFilters"
      >
        <option :value="null">Все города</option>
        <option v-for="city in cities" :key="city.id" :value="city.id">
          {{ city.name }}
        </option>
      </select>
    </div>

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
              ? 'bg-indigo-600 text-white'
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
          v-for="preset in datePresets"
          :key="preset.value"
          type="button"
          :class="[
            'px-3 py-1 rounded-md text-sm font-medium transition-colors',
            selectedPreset === preset.value
              ? 'bg-indigo-600 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
          ]"
          @click="selectDatePreset(preset.value)"
        >
          {{ preset.label }}
        </button>
      </div>

      <!-- Custom date inputs (shown when no preset is active) -->
      <div v-if="selectedPreset === null" class="space-y-2">
        <input
          v-model="localFilters.dateFrom"
          type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
          placeholder="Дата от"
          @change="emitFilters"
        />
        <input
          v-model="localFilters.dateTo"
          type="date"
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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
import { ref, reactive, computed } from 'vue';

const props = defineProps({
  cities: {
    type: Array,
    required: true,
  },
  sports: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: Object,
    default: () => ({
      cityId: null,
      sportIds: [],
      dateFrom: null,
      dateTo: null,
    }),
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

// Local filters state
const localFilters = reactive({
  cityId: props.modelValue.cityId,
  sportIds: [...(props.modelValue.sportIds || [])],
  dateFrom: props.modelValue.dateFrom,
  dateTo: props.modelValue.dateTo,
});

// Date presets
const datePresets = [
  { value: 'today', label: 'Сегодня' },
  { value: 'tomorrow', label: 'Завтра' },
  { value: 'week', label: 'На этой неделе' },
];

const selectedPreset = ref(null);

// Toggle sport selection
const toggleSport = (sportId) => {
  const index = localFilters.sportIds.indexOf(sportId);
  if (index > -1) {
    localFilters.sportIds.splice(index, 1);
  } else {
    localFilters.sportIds.push(sportId);
  }
  emitFilters();
};

// Select date preset
const selectDatePreset = (preset) => {
  selectedPreset.value = preset;

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  switch (preset) {
    case 'today':
      localFilters.dateFrom = formatDate(today);
      localFilters.dateTo = formatDate(today);
      break;
    case 'tomorrow':
      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);
      localFilters.dateFrom = formatDate(tomorrow);
      localFilters.dateTo = formatDate(tomorrow);
      break;
    case 'week':
      const weekEnd = new Date(today);
      weekEnd.setDate(weekEnd.getDate() + 7);
      localFilters.dateFrom = formatDate(today);
      localFilters.dateTo = formatDate(weekEnd);
      break;
  }

  emitFilters();
};

// Format date to YYYY-MM-DD
const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Reset all filters
const resetFilters = () => {
  localFilters.cityId = null;
  localFilters.sportIds = [];
  localFilters.dateFrom = null;
  localFilters.dateTo = null;
  selectedPreset.value = null;
  emitFilters();
};

// Emit filter changes
const emitFilters = () => {
  emit('update:modelValue', {
    cityId: localFilters.cityId,
    sportIds: [...localFilters.sportIds],
    dateFrom: localFilters.dateFrom,
    dateTo: localFilters.dateTo,
  });
  emit('change', {
    cityId: localFilters.cityId,
    sportIds: [...localFilters.sportIds],
    dateFrom: localFilters.dateFrom,
    dateTo: localFilters.dateTo,
  });
};
</script>
