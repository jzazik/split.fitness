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
        <div class="text-center">
          <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent"></div>
          <p class="mt-2 text-sm text-gray-600">Загрузка тренировок...</p>
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
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useMarkerCluster } from '@/composables/useMarkerCluster';

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

const emit = defineEmits(['workout-selected']);

const mapContainer = ref(null);
const loading = ref(false);
const workouts = ref([]);
let map = null;
let markerClusterGroup = null;

// Filters state
const filters = reactive({
  cityId: null,
  sportIds: [],
  dateFrom: null,
  dateTo: null,
});

// Fix for default marker icon not showing in production builds
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

onMounted(() => {
  if (typeof window !== 'undefined' && mapContainer.value) {
    initMap();
    loadWorkouts();
  }
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

const initMap = () => {
  // Create map instance with default center (Moscow)
  map = L.map(mapContainer.value).setView([55.7558, 37.6173], 11);

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  // Create cluster group using composable
  const { createClusterGroup } = useMarkerCluster();
  markerClusterGroup = createClusterGroup();
  map.addLayer(markerClusterGroup);
};

const loadWorkouts = async () => {
  loading.value = true;

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
  } finally {
    loading.value = false;
  }
};

// Handle filter changes
const handleFilterChange = () => {
  loadWorkouts();
};

const addWorkoutMarker = (workout) => {
  const marker = L.marker([workout.lat, workout.lng]);

  // Create popup content
  const popupContent = `
    <div class="p-2">
      <div class="font-semibold text-sm">${workout.sport_name || 'Тренировка'}</div>
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
        ${workout.coach_name || 'Тренер'}
      </div>
      <div class="text-xs font-semibold mt-1">
        ${workout.slot_price} ₽
      </div>
    </div>
  `;

  marker.bindPopup(popupContent);

  // Handle marker click
  marker.on('click', () => {
    emit('workout-selected', workout.id);
  });

  // Add marker to cluster group
  markerClusterGroup.addLayer(marker);
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
