<template>
  <PublicLayout hide-footer full-screen>
    <Head title="Карта тренировок" />

    <div class="relative h-full">
      <div ref="mapContainer" class="w-full h-full"></div>

      <MapFilters
        v-model="filters"
        :sports="sports"
        @change="handleFilterChange"
      />

      <MobileMapFilters
        v-model="filters"
        :sports="sports"
        @change="handleFilterChange"
      />

      <!-- Geolocation button -->
      <Transition name="geo-btn">
        <button
          v-show="!workoutCardExpanded"
          type="button"
          class="absolute z-[1000] right-3 md:top-4 md:bottom-auto md:right-4 flex items-center justify-center size-11 rounded-full bg-white shadow-lg border border-gray-200 text-gray-600 hover:text-primary-600 active:bg-gray-50 transition-all duration-300"
          :class="[
            geoLocating ? 'text-primary-500' : '',
            selectedWorkout ? 'bottom-44' : 'bottom-6',
          ]"
          :disabled="geoLocating"
          @click="goToMyLocation"
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
      </Transition>

      <div
        v-if="initialLoading"
        class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-[1000]"
      >
        <LoadingSpinner size="md" message="Загрузка тренировок..." />
      </div>

      <div
        v-if="!initialLoading && workouts.length === 0"
        class="absolute inset-0 flex items-center justify-center z-[999]"
        :class="hasError ? 'pointer-events-auto' : 'pointer-events-none'"
      >
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm text-center">
          <svg
            v-if="!hasError"
            class="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
            />
          </svg>
          <svg
            v-else
            class="mx-auto h-12 w-12 text-red-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          <h3 class="mt-2 text-sm font-semibold text-gray-900">
            {{ hasError ? 'Ошибка загрузки' : 'Нет тренировок' }}
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ hasError ? 'Не удалось загрузить тренировки. Попробуйте снова.' : 'Нет тренировок по выбранным фильтрам. Попробуйте изменить параметры поиска.' }}
          </p>
          <button
            v-if="hasError"
            @click="retryAfterError"
            class="mt-4 inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
          >
            Повторить попытку
          </button>
        </div>
      </div>

      <SearchResultsSheet
        ref="searchResultsSheet"
        :workouts="workouts"
        :selected-workout="selectedWorkout"
        @select="selectWorkoutFromSheet"
      />

      <WorkoutBottomCard
        :workout="selectedWorkout"
        :is-open="!!selectedWorkout"
        @close="closeWorkoutCard"
        @update:expanded="workoutCardExpanded = $event"
      />

      <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-[1001]"
      >
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
          <Toast
            :show="toast.show"
            :type="toast.type"
            :message="toast.message"
            @close="closeToast"
          />
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import MapFilters from '@/Components/Map/MapFilters.vue';
import MobileMapFilters from '@/Components/Map/MobileMapFilters.vue';
import WorkoutBottomCard from '@/Components/Map/WorkoutBottomCard.vue';
import SearchResultsSheet from '@/Components/Map/SearchResultsSheet.vue';
import LoadingSpinner from '@/Components/UI/LoadingSpinner.vue';
import Toast from '@/Components/UI/Toast.vue';
import { debounce } from '@/utils/debounce';
import { formatPrice } from '@/utils/workout';

let ymaps3 = null;

const props = defineProps({
  sports: { type: Array, required: true },
});

const mapContainer = ref(null);
const searchResultsSheet = ref(null);
const initialLoading = ref(true);
const hasError = ref(false);
const workouts = ref([]);
const selectedWorkout = ref(null);

let map = null;
let clusterer = null;
let clusterByGrid = null;
let YMapClusterer = null;
let YMapMarker = null;
let toastTimeout = null;
let isInitialLoad = true;
let currentRequestController = null;
let isMounted = false;
let debouncedLoadWorkouts = null;

const geoLocating = ref(false);
const workoutCardExpanded = ref(false);

const filters = reactive({
  sportIds: [],
  dateFrom: null,
  dateTo: null,
});

const toast = reactive({
  show: false,
  type: 'info',
  message: '',
});

const initBootstrap = async () => {
  if (typeof window === 'undefined' || !mapContainer.value) return;

  try {
    const { loadYandexMaps } = await import('@/composables/useYandexMap');
    ymaps3 = await loadYandexMaps();
    if (!ymaps3 || !isMounted) return;

    const { useMarkerCluster } = await import('@/composables/useMarkerCluster');
    const clusterModule = await useMarkerCluster();
    YMapClusterer = clusterModule.YMapClusterer;
    clusterByGrid = clusterModule.clusterByGrid;
    YMapMarker = ymaps3.YMapMarker;

    if (!isMounted) return;

    initMap();
    loadWorkouts();
  } catch (error) {
    console.error('Failed to initialize map bootstrap:', error);
    hasError.value = true;
    workouts.value = [];
    showToast('error', 'Не удалось загрузить компоненты карты. Попробуйте обновить страницу.');
  }
};

onMounted(async () => {
  isMounted = true;
  await initBootstrap();
});

onBeforeUnmount(() => {
  isMounted = false;

  if (currentRequestController) {
    currentRequestController.abort();
    currentRequestController = null;
  }
  if (toastTimeout) {
    clearTimeout(toastTimeout);
    toastTimeout = null;
  }
  if (typeof handleFilterChange.cancel === 'function') {
    handleFilterChange.cancel();
  }
  if (debouncedLoadWorkouts && typeof debouncedLoadWorkouts.cancel === 'function') {
    debouncedLoadWorkouts.cancel();
  }

  clusterer = null;
  if (map) {
    map.destroy();
    map = null;
  }
});

const initMap = () => {
  if (!isMounted || !mapContainer.value || !ymaps3) return;

  const initialCenter = [37.6173, 55.7558];
  const initialZoom = 11;

  const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapFeatureDataSource, YMapLayer, YMapListener } = ymaps3;

  map = new YMap(
    mapContainer.value,
    {
      location: { center: initialCenter, zoom: initialZoom },
      showScaleInCopyrights: true,
    },
    [
      new YMapDefaultSchemeLayer({}),
      new YMapDefaultFeaturesLayer({}),
      new YMapFeatureDataSource({ id: 'clusterer-source' }),
      new YMapLayer({ source: 'clusterer-source', type: 'markers', zIndex: 1800 }),
    ]
  );

  debouncedLoadWorkouts = debounce(() => {
    if (isInitialLoad) {
      isInitialLoad = false;
      return;
    }
    loadWorkouts();
  }, 500);

  const listener = new YMapListener({
    layer: 'any',
    onActionEnd: debouncedLoadWorkouts,
  });
  map.addChild(listener);
};

const buildFeatures = (workoutList) => {
  return workoutList
    .filter(w => w.lat != null && w.lng != null)
    .map(w => ({
      type: 'Feature',
      id: String(w.id),
      geometry: {
        type: 'Point',
        coordinates: [parseFloat(w.lng), parseFloat(w.lat)],
      },
      properties: { workout: w },
    }));
};

const getAvailabilityStatus = (workout) => {
  const total = workout.slots_total || 0;
  const booked = workout.slots_booked || 0;
  const available = total - booked;
  if (available <= 0) return 'full';
  if (total > 0 && available / total < 0.3) return 'low';
  return 'available';
};

const createMarkerElement = (workout) => {
  const el = document.createElement('div');
  const status = getAvailabilityStatus(workout);
  el.className = `ym-workout-pin ym-workout-pin--${status}`;
  el.innerHTML = `
    <svg width="32" height="40" viewBox="0 0 32 40" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M16 0C7.163 0 0 7.163 0 16c0 10 16 24 16 24s16-14 16-24C32 7.163 24.837 0 16 0z" fill="currentColor"/>
      <circle cx="16" cy="16" r="7" fill="white"/>
    </svg>
    <span class="ym-workout-pin__price">${escapeHtml(formatPrice(workout.slot_price))} ₽</span>
  `;
  el.addEventListener('click', () => {
    selectedWorkout.value = workout;
  });
  return el;
};

const createClusterElement = (count) => {
  const el = document.createElement('div');
  el.className = 'ym-cluster-marker';
  el.innerHTML = `<div class="ym-cluster-content"><span>${count}</span></div>`;
  return el;
};

const updateClusterer = (workoutList) => {
  if (!map || !YMapClusterer || !clusterByGrid) return;

  if (clusterer) {
    map.removeChild(clusterer);
    clusterer = null;
  }

  const features = buildFeatures(workoutList);
  if (features.length === 0) return;

  const markerFactory = (feature) => {
    return new YMapMarker(
      { coordinates: feature.geometry.coordinates, source: 'clusterer-source' },
      createMarkerElement(feature.properties.workout)
    );
  };

  const clusterFactory = (coordinates, features) => {
    const el = createClusterElement(features.length);
    el.addEventListener('click', () => {
      const allCoords = features.map(f => f.geometry.coordinates);
      const lngs = allCoords.map(c => c[0]);
      const lats = allCoords.map(c => c[1]);

      const minLng = Math.min(...lngs);
      const maxLng = Math.max(...lngs);
      const minLat = Math.min(...lats);
      const maxLat = Math.max(...lats);

      const lngSpan = maxLng - minLng || 0.005;
      const latSpan = maxLat - minLat || 0.005;
      const pad = 0.35;

      map.setLocation({
        bounds: [
          [minLng - lngSpan * pad, minLat - latSpan * pad],
          [maxLng + lngSpan * pad, maxLat + latSpan * pad],
        ],
        duration: 400,
      });

      setTimeout(() => loadWorkouts(), 450);
    });
    return new YMapMarker({ coordinates, source: 'clusterer-source' }, el);
  };

  clusterer = new YMapClusterer({
    method: clusterByGrid({ gridSize: 64 }),
    features,
    marker: markerFactory,
    cluster: clusterFactory,
  });

  map.addChild(clusterer);
};

const loadWorkouts = async () => {
  if (!isMounted || !ymaps3 || !map) return;

  if (currentRequestController) {
    currentRequestController.abort();
  }
  const controller = new AbortController();
  currentRequestController = controller;

  hasError.value = false;

  try {
    const params = {};

    if (filters.sportIds && filters.sportIds.length > 0) params.sport_id = filters.sportIds;
    if (filters.dateFrom) params.date_from = filters.dateFrom;
    if (filters.dateTo) params.date_to = filters.dateTo;

    if (map.bounds) {
      const [[lng1, lat1], [lng2, lat2]] = map.bounds;
      const round6 = (v) => Math.round(v * 1e6) / 1e6;
      params.sw_lat = round6(Math.min(lat1, lat2));
      params.ne_lat = round6(Math.max(lat1, lat2));
      params.sw_lng = round6(Math.min(lng1, lng2));
      params.ne_lng = round6(Math.max(lng1, lng2));
    }

    const response = await window.axios.get('/api/workouts/map', {
      params,
      signal: controller.signal,
    });

    if (response.data.meta?.truncated) {
      showToast('warning', 'Показано максимум 200 тренировок. Приблизьте карту для детального просмотра.');
    }

    workouts.value = response.data.data;
    updateClusterer(workouts.value);
  } catch (error) {
    if (error.name === 'AbortError' || error.code === 'ERR_CANCELED') return;
    console.error('Failed to load workouts:', error);
    hasError.value = true;
    workouts.value = [];

    if (clusterer && map) {
      map.removeChild(clusterer);
      clusterer = null;
    }

    showToast('error', 'Не удалось загрузить тренировки.');
  } finally {
    if (currentRequestController === controller) {
      initialLoading.value = false;
      currentRequestController = null;
    }
  }
};

const handleFilterChange = debounce(() => {
  loadWorkouts();
}, 300);

const escapeHtml = (text) => {
  if (!text) return '';
  const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return String(text).replace(/[&<>"']/g, (m) => entities[m]);
};

const selectWorkoutFromSheet = (workout) => {
  searchResultsSheet.value?.close();
  selectedWorkout.value = workout;

  if (map && workout.lat && workout.lng) {
    map.setLocation({
      center: [parseFloat(workout.lng), parseFloat(workout.lat)],
      zoom: Math.max(map.zoom ?? 14, 14),
      duration: 400,
    });
  }
};

const closeWorkoutCard = () => {
  selectedWorkout.value = null;
  workoutCardExpanded.value = false;
};

const showToast = (type, message) => {
  if (toastTimeout) clearTimeout(toastTimeout);

  toast.show = true;
  toast.type = type;
  toast.message = message;

  const dismissTime = type === 'warning' ? 8000 : 5000;
  toastTimeout = setTimeout(() => closeToast(), dismissTime);
};

const closeToast = () => {
  toast.show = false;
  if (toastTimeout) {
    clearTimeout(toastTimeout);
    toastTimeout = null;
  }
};

const goToMyLocation = () => {
  if (!navigator.geolocation || !map) return;

  geoLocating.value = true;
  navigator.geolocation.getCurrentPosition(
    (position) => {
      geoLocating.value = false;
      const { longitude, latitude } = position.coords;
      map.setLocation({
        center: [longitude, latitude],
        zoom: 14,
        duration: 400,
      });
      setTimeout(() => loadWorkouts(), 450);
    },
    () => {
      geoLocating.value = false;
      showToast('error', 'Не удалось определить местоположение. Проверьте разрешения.');
    },
    { enableHighAccuracy: true, timeout: 10000 },
  );
};

const retryAfterError = async () => {
  if (!ymaps3 || !map || !YMapClusterer) {
    if (map) {
      map.destroy();
      map = null;
    }
    clusterer = null;
    ymaps3 = null;
    await initBootstrap();
  } else {
    await loadWorkouts();
  }
};
</script>

<style scoped>
.geo-btn-enter-active,
.geo-btn-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.geo-btn-enter-from,
.geo-btn-leave-to {
  opacity: 0;
  transform: scale(0.8);
}

:deep(.ym-workout-pin) {
  position: relative;
  cursor: pointer;
  transform: translate(-50%, -100%);
  transition: transform 0.15s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #f04e23;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.25));
}

:deep(.ym-workout-pin:hover) {
  transform: translate(-50%, -100%) scale(1.12);
}

:deep(.ym-workout-pin--available) {
  color: #f04e23;
}

:deep(.ym-workout-pin--low) {
  color: #f59e0b;
}

:deep(.ym-workout-pin--full) {
  color: #9ca3af;
}

:deep(.ym-workout-pin__price) {
  position: absolute;
  bottom: -18px;
  white-space: nowrap;
  background: white;
  border-radius: 9999px;
  padding: 1px 6px;
  font-size: 10px;
  font-weight: 600;
  color: #374151;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
  pointer-events: none;
}

:deep(.ym-cluster-marker) {
  cursor: pointer;
  transform: translate(-50%, -50%);
}

:deep(.ym-cluster-content) {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(240, 78, 35, 0.9);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(240, 78, 35, 0.4);
}
</style>
