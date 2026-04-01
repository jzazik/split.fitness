<template>
  <PublicLayout>
    <Head title="Карта тренировок" />

    <div class="relative h-screen">
      <!-- Map Container (Fullscreen) -->
      <div ref="mapContainer" class="w-full h-full"></div>

      <!-- Map Filters -->
      <MapFilters
        v-model="filters"
        :cities="cities"
        :sports="sports"
        @change="handleFilterChange"
      />

      <!-- Loading State -->
      <div
        v-if="loading"
        class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-[1000]"
      >
        <LoadingSpinner size="md" message="Загрузка тренировок..." />
      </div>

      <!-- Empty State -->
      <div
        v-if="!loading && workouts.length === 0"
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
            @click="loadWorkouts"
            class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Повторить попытку
          </button>
        </div>
      </div>

      <!-- Workout Bottom Card -->
      <WorkoutBottomCard
        :workout="selectedWorkout"
        :is-open="!!selectedWorkout"
        @close="closeWorkoutCard"
      />

      <!-- Toast Notification -->
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
import { ref, reactive, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import MapFilters from '@/Components/Map/MapFilters.vue';
import WorkoutBottomCard from '@/Components/Map/WorkoutBottomCard.vue';
import LoadingSpinner from '@/Components/UI/LoadingSpinner.vue';
import Toast from '@/Components/UI/Toast.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useMarkerCluster } from '@/composables/useMarkerCluster';
import { debounce } from '@/utils/debounce';

const props = defineProps({
  cities: {
    type: Array,
    required: true,
  },
  sports: {
    type: Array,
    required: true,
  },
});

const page = usePage();
const mapContainer = ref(null);
const loading = ref(false);
const hasError = ref(false);
const workouts = ref([]);
const selectedWorkout = ref(null);
let map = null;
let markerClusterGroup = null;
let toastTimeout = null;
let isInitialLoad = true;

// Initialize filters from URL query parameters
const urlParams = new URLSearchParams(window.location.search);
const filters = reactive({
  cityId: urlParams.get('city_id') ? parseInt(urlParams.get('city_id')) : null,
  sportIds: [],
  dateFrom: null,
  dateTo: null,
});

// Toast state
const toast = reactive({
  show: false,
  type: 'info',
  message: '',
});

// Fix for default marker icon not showing in production builds
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

// Watch for city filter changes and recenter map
watch(() => filters.cityId, (newCityId) => {
  if (map && newCityId) {
    const selectedCity = props.cities.find(city => city.id === newCityId);
    if (selectedCity && selectedCity.lat && selectedCity.lng) {
      map.setView([selectedCity.lat, selectedCity.lng], 12);
    }
  }
});

onMounted(() => {
  if (typeof window !== 'undefined' && mapContainer.value) {
    initMap();
    loadWorkouts();
  }
});

onBeforeUnmount(() => {
  // Clear toast timeout
  if (toastTimeout) {
    clearTimeout(toastTimeout);
    toastTimeout = null;
  }

  // Clean up marker cluster group
  if (markerClusterGroup) {
    markerClusterGroup.clearLayers();
    markerClusterGroup.remove();
    markerClusterGroup = null;
  }

  // Clean up map and event listeners
  if (map) {
    if (debouncedLoadWorkouts) {
      map.off('moveend', debouncedLoadWorkouts);
      if (typeof debouncedLoadWorkouts.cancel === 'function') {
        debouncedLoadWorkouts.cancel();
      }
      debouncedLoadWorkouts = null;
    }
    map.remove();
    map = null;
  }
});

let debouncedLoadWorkouts = null;

const initMap = () => {
  try {
    // Determine initial map center
    let initialCenter = [55.7558, 37.6173]; // Default: Moscow
    let initialZoom = 11;

    // If city is selected via URL param, center on that city
    if (filters.cityId) {
      const selectedCity = props.cities.find(city => city.id === filters.cityId);
      if (selectedCity && selectedCity.lat && selectedCity.lng) {
        initialCenter = [selectedCity.lat, selectedCity.lng];
        initialZoom = 12;
      }
    }

    // Create map instance
    map = L.map(mapContainer.value).setView(initialCenter, initialZoom);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);

    // Create cluster group using composable
    const { createClusterGroup } = useMarkerCluster();
    markerClusterGroup = createClusterGroup();
    map.addLayer(markerClusterGroup);

    // Add debounced viewport change listener for bbox optimization
    debouncedLoadWorkouts = debounce(() => {
      // Skip the first moveend event (fired right after initialization)
      if (isInitialLoad) {
        isInitialLoad = false;
        return;
      }
      loadWorkouts();
    }, 500);

    map.on('moveend', debouncedLoadWorkouts);
  } catch (error) {
    console.error('Failed to initialize map:', error);
    showToast('error', 'Не удалось загрузить карту. Попробуйте обновить страницу.');
  }
};

const loadWorkouts = async () => {
  loading.value = true;
  hasError.value = false;

  try {
    // Build query parameters from filters
    const params = {};

    if (filters.cityId) {
      params.city_id = filters.cityId;
    }

    if (filters.sportIds && filters.sportIds.length > 0) {
      params.sport_id = filters.sportIds;
    }

    if (filters.dateFrom) {
      params.date_from = filters.dateFrom;
    }

    if (filters.dateTo) {
      params.date_to = filters.dateTo;
    }

    // Add bounding box parameters from current viewport
    if (map) {
      const bounds = map.getBounds();
      const northEast = bounds.getNorthEast();
      const southWest = bounds.getSouthWest();

      params.ne_lat = northEast.lat;
      params.ne_lng = northEast.lng;
      params.sw_lat = southWest.lat;
      params.sw_lng = southWest.lng;
    }

    const response = await window.axios.get('/api/workouts/map', { params });
    workouts.value = response.data.data;

    // Clear existing markers
    if (markerClusterGroup) {
      markerClusterGroup.clearLayers();
    }

    // Add markers for each workout
    workouts.value.forEach(workout => {
      if (workout.lat && workout.lng) {
        addWorkoutMarker(workout);
      }
    });

  } catch (error) {
    console.error('Failed to load workouts:', error);
    hasError.value = true;
    showToast('error', 'Не удалось загрузить тренировки.');
  } finally {
    loading.value = false;
  }
};

// Handle filter changes with debouncing
const handleFilterChange = debounce(() => {
  loadWorkouts();
}, 300);

// Escape HTML to prevent XSS
const escapeHtml = (text) => {
  if (!text) return '';
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, (m) => map[m]);
};

const addWorkoutMarker = (workout) => {
  const lat = parseFloat(workout.lat);
  const lng = parseFloat(workout.lng);

  if (isNaN(lat) || isNaN(lng)) {
    console.warn('Invalid coordinates for workout:', workout.id);
    return;
  }

  const marker = L.marker([lat, lng]);

  // Create popup content with escaped HTML
  const popupContent = `
    <div class="p-2">
      <div class="font-semibold text-sm">${escapeHtml(workout.sport_name || 'Тренировка')}</div>
      <div class="text-xs text-gray-600 mt-1">
        ${new Date(workout.starts_at).toLocaleString('ru-RU', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        })}
      </div>
      <div class="text-xs text-gray-600 mt-1">
        ${escapeHtml(workout.coach_name || 'Тренер')}
      </div>
      <div class="text-xs font-semibold mt-1">
        ${escapeHtml(String(workout.slot_price))} ₽
      </div>
    </div>
  `;

  marker.bindPopup(popupContent);

  // Handle marker click
  marker.on('click', () => {
    selectedWorkout.value = workout;
  });

  // Add marker to cluster group
  markerClusterGroup.addLayer(marker);
};

const closeWorkoutCard = () => {
  selectedWorkout.value = null;
};

const showToast = (type, message) => {
  // Clear existing timeout
  if (toastTimeout) {
    clearTimeout(toastTimeout);
  }

  toast.show = true;
  toast.type = type;
  toast.message = message;

  // Auto-dismiss after 5 seconds
  toastTimeout = setTimeout(() => {
    closeToast();
  }, 5000);
};

const closeToast = () => {
  toast.show = false;
  if (toastTimeout) {
    clearTimeout(toastTimeout);
    toastTimeout = null;
  }
};
</script>

<style scoped>
/* Ensure map takes full height */
:deep(.leaflet-container) {
  height: 100%;
  width: 100%;
}

/* Ensure Leaflet controls are visible and above other content */
:deep(.leaflet-control-zoom) {
  border: 2px solid rgba(0, 0, 0, 0.2);
  z-index: 1000;
}

:deep(.leaflet-bar a) {
  color: #000;
}

/* Cluster marker styles */
:deep(.marker-cluster) {
  background-color: rgba(79, 70, 229, 0.6);
  border-radius: 50%;
  text-align: center;
  color: white;
  font-weight: bold;
}

:deep(.marker-cluster div) {
  width: 40px;
  height: 40px;
  margin-left: 0;
  margin-top: 0;
  text-align: center;
  border-radius: 50%;
  background-color: rgba(79, 70, 229, 0.8);
}

:deep(.marker-cluster span) {
  line-height: 40px;
}

:deep(.marker-cluster-small) {
  background-color: rgba(79, 70, 229, 0.6);
}

:deep(.marker-cluster-small div) {
  background-color: rgba(79, 70, 229, 0.8);
}

:deep(.marker-cluster-medium) {
  background-color: rgba(67, 56, 202, 0.6);
}

:deep(.marker-cluster-medium div) {
  background-color: rgba(67, 56, 202, 0.8);
}

:deep(.marker-cluster-large) {
  background-color: rgba(55, 48, 163, 0.6);
}

:deep(.marker-cluster-large div) {
  background-color: rgba(55, 48, 163, 0.8);
}
</style>
