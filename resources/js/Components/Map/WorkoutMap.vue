<template>
  <div>
    <div ref="mapContainer" class="w-full h-96 rounded-lg border border-gray-300 shadow-sm"></div>
    <p v-if="!mapReady" class="text-sm text-gray-500 mt-2">Загрузка карты...</p>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  initialLat: {
    type: Number,
    default: 55.7558, // Moscow center
  },
  initialLng: {
    type: Number,
    default: 37.6173,
  },
  editable: {
    type: Boolean,
    default: true,
  },
  modelValue: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue', 'coordinates-selected']);

const mapContainer = ref(null);
const mapReady = ref(false);
let map = null;
let marker = null;

// Fix for default marker icon not showing in production builds
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

onMounted(() => {
  // Initialize map only in browser (not during SSR)
  if (typeof window !== 'undefined' && mapContainer.value) {
    initMap();
  }
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

const initMap = () => {
  // Create map instance
  map = L.map(mapContainer.value).setView([props.initialLat, props.initialLng], 13);

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  // Add initial marker if coordinates are provided
  if (props.modelValue && props.modelValue.lat && props.modelValue.lng) {
    addMarker(props.modelValue.lat, props.modelValue.lng);
  }

  // Handle map clicks if editable
  if (props.editable) {
    map.on('click', (e) => {
      const { lat, lng } = e.latlng;
      addMarker(lat, lng);
      emitCoordinates(lat, lng);
    });
  }

  mapReady.value = true;
};

const addMarker = (lat, lng) => {
  // Remove existing marker if present
  if (marker) {
    map.removeLayer(marker);
  }

  // Add new marker
  marker = L.marker([lat, lng]).addTo(map);

  // Center map on marker
  map.setView([lat, lng], map.getZoom());
};

const emitCoordinates = (lat, lng) => {
  const coordinates = {
    lat: parseFloat(lat.toFixed(8)),
    lng: parseFloat(lng.toFixed(8)),
  };

  emit('update:modelValue', coordinates);
  emit('coordinates-selected', coordinates);
};

// Watch for external coordinate updates
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue && newValue.lat && newValue.lng && map) {
      addMarker(newValue.lat, newValue.lng);
    }
  },
  { deep: true }
);
</script>

<style scoped>
/* Ensure Leaflet controls are visible */
:deep(.leaflet-control-zoom) {
  border: 2px solid rgba(0, 0, 0, 0.2);
}

:deep(.leaflet-bar a) {
  color: #000;
}
</style>
