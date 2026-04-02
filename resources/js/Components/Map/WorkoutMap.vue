<template>
  <div>
    <div ref="mapContainer" class="w-full h-96 rounded-lg border border-gray-300 shadow-sm"></div>
    <p v-if="!mapReady" class="text-sm text-gray-500 mt-2">Загрузка карты...</p>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
  initialLat: {
    type: Number,
    default: 55.7558,
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
let ymaps3 = null;
let map = null;
let marker = null;
let YMapMarker = null;

onMounted(async () => {
  if (typeof window === 'undefined' || !mapContainer.value) return;

  try {
    const { loadYandexMaps } = await import('@/composables/useYandexMap');
    ymaps3 = await loadYandexMaps();
    if (!ymaps3) return;

    const { YMapDefaultMarker } = await ymaps3.import('@yandex/ymaps3-default-ui-theme');
    YMapMarker = ymaps3.YMapMarker;

    initMap(YMapDefaultMarker);
  } catch (error) {
    console.error('Failed to load Yandex Maps:', error);
  }
});

onBeforeUnmount(() => {
  if (map) {
    map.destroy();
    map = null;
  }
});

const initMap = (YMapDefaultMarker) => {
  const { YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapListener } = ymaps3;

  const startLng = props.modelValue?.lng ?? props.initialLng;
  const startLat = props.modelValue?.lat ?? props.initialLat;

  map = new YMap(
    mapContainer.value,
    {
      location: { center: [startLng, startLat], zoom: 13 },
      showScaleInCopyrights: true,
    },
    [
      new YMapDefaultSchemeLayer({}),
      new YMapDefaultFeaturesLayer({}),
    ]
  );

  if (props.modelValue && props.modelValue.lat && props.modelValue.lng) {
    addMarker(props.modelValue.lat, props.modelValue.lng);
  }

  if (props.editable) {
    const listener = new YMapListener({
      layer: 'any',
      onClick: (_, event) => {
        if (!event || !event.coordinates) return;
        const [lng, lat] = event.coordinates;
        addMarker(lat, lng);
        emitCoordinates(lat, lng);
      },
    });
    map.addChild(listener);
  }

  mapReady.value = true;
};

const addMarker = (lat, lng) => {
  if (marker && map) {
    map.removeChild(marker);
  }

  const el = document.createElement('div');
  el.className = 'ym-pin-marker';

  marker = new YMapMarker(
    { coordinates: [lng, lat] },
    el
  );
  map.addChild(marker);

  map.setLocation({
    center: [lng, lat],
    zoom: map.zoom,
    duration: 200,
  });
};

const emitCoordinates = (lat, lng) => {
  const coordinates = {
    lat: parseFloat(parseFloat(lat).toFixed(8)),
    lng: parseFloat(parseFloat(lng).toFixed(8)),
  };
  emit('update:modelValue', coordinates);
  emit('coordinates-selected', coordinates);
};

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
:deep(.ym-pin-marker) {
  width: 24px;
  height: 24px;
  background: rgb(79, 70, 229);
  border: 3px solid white;
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
  transform: translate(-50%, -50%);
}
</style>
