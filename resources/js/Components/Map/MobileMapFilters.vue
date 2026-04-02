<template>
  <div class="md:hidden absolute bottom-[calc(1rem+env(safe-area-inset-bottom,0px))] left-0 right-0 z-[1000] px-3">
    <div class="flex items-center gap-2">
      <div class="flex gap-2 overflow-x-auto no-scrollbar min-w-0">
        <!-- Sport chip -->
        <button
          type="button"
          :class="chipClass(localFilters.sportIds.length > 0)"
          @click="openSheet('sport')"
        >
          <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" v-html="activeSportIcon" />
          <span class="truncate">{{ activeSportLabel }}</span>
          <svg v-if="localFilters.sportIds.length > 0" class="size-3.5 shrink-0 -mr-0.5" viewBox="0 0 20 20" fill="currentColor" @click.stop="clearSports">
            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
          </svg>
          <svg v-else class="size-3.5 shrink-0 -mr-0.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
          </svg>
        </button>

        <!-- Date chip -->
        <button
          type="button"
          :class="chipClass(localFilters.dateFrom !== null)"
          @click="openSheet('date')"
        >
          <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="truncate">{{ activeDateLabel }}</span>
          <svg v-if="localFilters.dateFrom !== null" class="size-3.5 shrink-0 -mr-0.5" viewBox="0 0 20 20" fill="currentColor" @click.stop="clearDates">
            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
          </svg>
          <svg v-else class="size-3.5 shrink-0 -mr-0.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
          </svg>
        </button>

        <!-- Reset -->
        <button
          v-if="hasActiveFilters"
          type="button"
          class="flex items-center gap-1 whitespace-nowrap rounded-full px-3 py-2 text-xs font-medium bg-red-50 text-red-600 shadow-sm border border-red-200 active:bg-red-100 transition-colors"
          @click="resetFilters"
        >
          <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
          </svg>
          Сбросить
        </button>
      </div>

      <!-- Geolocation -->
      <button
        type="button"
        class="ml-auto flex items-center justify-center size-10 rounded-full bg-white shadow-sm border border-gray-200 text-gray-600 active:bg-gray-50 transition-colors shrink-0"
        :class="geoLocating ? 'text-primary-500' : ''"
        :disabled="geoLocating"
        @click="emit('geolocate')"
        aria-label="Моё местоположение"
      >
        <svg v-if="!geoLocating" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2m10-10h-2M4 12H2" />
        </svg>
        <svg v-else class="size-5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </button>
    </div>
  </div>

  <BottomSheet v-model="sheetOpen" :title="sheetTitle">
    <!-- Sport sheet -->
    <div v-if="activeSheet === 'sport'" class="px-4 pb-6">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="sport in sports"
          :key="sport.id"
          type="button"
          :class="[
            'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium transition-colors',
            localFilters.sportIds.includes(sport.id)
              ? 'bg-primary-500 text-white shadow-sm'
              : 'bg-gray-100 text-gray-700 active:bg-gray-200'
          ]"
          @click="toggleSport(sport.id)"
        >
          <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" v-html="getSportIconPaths(sport.slug)" />
          {{ sport.name }}
        </button>
      </div>
      <button
        v-if="localFilters.sportIds.length > 0"
        type="button"
        class="mt-4 w-full py-2.5 rounded-xl bg-primary-500 text-white text-sm font-semibold active:bg-primary-600 transition-colors"
        @click="sheetOpen = false"
      >
        Применить
      </button>
    </div>

    <!-- Date sheet -->
    <div v-if="activeSheet === 'date'" class="px-4 pb-6">
      <div class="flex flex-wrap gap-2 mb-4">
        <button
          v-for="preset in presets"
          :key="preset.value"
          type="button"
          :class="[
            'px-3.5 py-2 rounded-full text-sm font-medium transition-colors',
            selectedPreset === preset.value
              ? 'bg-primary-500 text-white shadow-sm'
              : 'bg-gray-100 text-gray-700 active:bg-gray-200'
          ]"
          @click="selectDatePreset(preset.value)"
        >
          {{ preset.label }}
        </button>
      </div>
      <div v-if="selectedPreset === 'custom'" class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">От</label>
          <input
            v-model="localFilters.dateFrom"
            type="date"
            :min="formatDate(new Date())"
            class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:border-primary-500 focus:ring-primary-500"
            @change="emitFilters"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-500 mb-1">До</label>
          <input
            v-model="localFilters.dateTo"
            type="date"
            :min="localFilters.dateFrom || formatDate(new Date())"
            class="w-full rounded-xl border-gray-200 text-sm py-2.5 focus:border-primary-500 focus:ring-primary-500"
            @change="emitFilters"
          />
        </div>
      </div>
    </div>
  </BottomSheet>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useDatePresets } from '@/composables/useDatePresets';
import { getSportIconPaths } from '@/utils/sportIcons';
import BottomSheet from '@/Components/UI/BottomSheet.vue';

const props = defineProps({
  sports: { type: Array, required: true },
  modelValue: {
    type: Object,
    default: () => ({
      sportIds: [],
      dateFrom: null,
      dateTo: null,
    }),
  },
  geoLocating: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'change', 'geolocate']);

const { presets, selectedPreset, computeRange, formatDate } = useDatePresets();

const localFilters = reactive({
  sportIds: [...(props.modelValue.sportIds || [])],
  dateFrom: props.modelValue.dateFrom,
  dateTo: props.modelValue.dateTo,
});

watch(() => props.modelValue, (v) => {
  localFilters.sportIds = [...(v.sportIds || [])];
  localFilters.dateFrom = v.dateFrom;
  localFilters.dateTo = v.dateTo;
}, { deep: true });

const activeSheet = ref(null);
const sheetOpen = ref(false);

watch(sheetOpen, (open) => {
  if (!open) activeSheet.value = null;
});

const sheetTitle = computed(() => {
  if (activeSheet.value === 'sport') return 'Вид спорта';
  if (activeSheet.value === 'date') return 'Дата';
  return '';
});

const hasActiveFilters = computed(() =>
  localFilters.sportIds.length > 0 ||
  localFilters.dateFrom !== null
);

const truncate = (str, max) => str.length > max ? str.slice(0, max) + '…' : str;

const activeSportLabel = computed(() => {
  const count = localFilters.sportIds.length;
  if (count === 0) return 'Спорт';
  if (count === 1) {
    const sport = props.sports.find(s => s.id === localFilters.sportIds[0]);
    return truncate(sport?.name ?? 'Спорт', 10);
  }
  return `Спорт · ${count}`;
});

const DEFAULT_SPORT_CHIP_ICON = '<path stroke-linecap="round" stroke-linejoin="round" stroke="currentColor" stroke-width="2" fill="none" d="M13 10V3L4 14h7v7l9-11h-7z"/>';

const activeSportIcon = computed(() => {
  if (localFilters.sportIds.length === 1) {
    const sport = props.sports.find(s => s.id === localFilters.sportIds[0]);
    if (sport) return getSportIconPaths(sport.slug);
  }
  return DEFAULT_SPORT_CHIP_ICON;
});

const activeDateLabel = computed(() => {
  if (!localFilters.dateFrom && selectedPreset.value !== 'custom') return 'Дата';
  if (selectedPreset.value && selectedPreset.value !== 'custom') {
    const p = presets.find(d => d.value === selectedPreset.value);
    return p?.label ?? 'Дата';
  }
  if (selectedPreset.value === 'custom' && localFilters.dateFrom) return 'Дата ✓';
  return 'Дата';
});

const chipClass = (active) => [
  'flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-2 text-xs font-medium shadow-sm transition-colors',
  active
    ? 'bg-primary-500 text-white border border-primary-500'
    : 'bg-white text-gray-700 border border-gray-200 active:bg-gray-50',
];

const openSheet = (type) => {
  activeSheet.value = type;
  sheetOpen.value = true;
};

const toggleSport = (sportId) => {
  const idx = localFilters.sportIds.indexOf(sportId);
  if (idx > -1) {
    localFilters.sportIds.splice(idx, 1);
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
    sheetOpen.value = false;
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
  sheetOpen.value = false;
};

const clearSports = () => {
  localFilters.sportIds = [];
  emitFilters();
};

const clearDates = () => {
  localFilters.dateFrom = null;
  localFilters.dateTo = null;
  selectedPreset.value = null;
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

<style scoped>
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
