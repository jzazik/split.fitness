<template>
  <TransitionRoot :show="isOpen" as="template">
    <div class="relative z-50">
      <!-- Overlay -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-25" @click="close" />
      </TransitionChild>

      <!-- Bottom Panel -->
      <div class="fixed inset-x-0 bottom-0 flex items-end justify-center pointer-events-none">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="translate-y-full"
          enter-to="translate-y-0"
          leave="ease-in duration-200"
          leave-from="translate-y-0"
          leave-to="translate-y-full"
        >
          <div
            class="w-full max-w-2xl bg-white rounded-t-2xl shadow-xl pointer-events-auto max-h-[80vh] overflow-y-auto"
          >
            <!-- Close Button -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900">Детали тренировки</h3>
              <button
                @click="close"
                class="text-gray-400 hover:text-gray-500 focus:outline-none"
              >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Content -->
            <div v-if="workout" class="p-6 space-y-6">
              <!-- Coach Info -->
              <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <img
                    v-if="workout.coach_avatar_url"
                    :src="workout.coach_avatar_url"
                    :alt="workout.coach_name"
                    class="h-16 w-16 rounded-full object-cover"
                  />
                  <div
                    v-else
                    class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center"
                  >
                    <span class="text-indigo-600 font-semibold text-xl">
                      {{ getInitials(workout.coach_name) }}
                    </span>
                  </div>
                </div>
                <div class="flex-1">
                  <h4 class="text-lg font-semibold text-gray-900">{{ workout.coach_name }}</h4>
                  <div v-if="workout.coach_rating" class="flex items-center mt-1">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="ml-1 text-sm text-gray-600">{{ workout.coach_rating.toFixed(1) }}</span>
                  </div>
                </div>
              </div>

              <!-- Sport Badge -->
              <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                  {{ workout.sport_name }}
                </span>
              </div>

              <!-- Location & Time Details -->
              <div class="space-y-3">
                <!-- Location -->
                <div class="flex items-start">
                  <svg class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ workout.location_name }}</p>
                    <p v-if="workout.address" class="text-sm text-gray-500">{{ workout.address }}</p>
                    <p v-if="workout.city_name" class="text-sm text-gray-500">{{ workout.city_name }}</p>
                  </div>
                </div>

                <!-- Date & Time -->
                <div class="flex items-start">
                  <svg class="h-5 w-5 text-gray-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">
                      {{ formatDate(workout.starts_at) }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ formatTime(workout.starts_at) }}
                      <span v-if="workout.duration_minutes">
                        · {{ workout.duration_minutes }} мин
                      </span>
                    </p>
                  </div>
                </div>
              </div>

              <!-- Price & Availability -->
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex justify-between items-center">
                  <div>
                    <p class="text-sm text-gray-600">Цена</p>
                    <p class="text-2xl font-bold text-gray-900">{{ workout.slot_price }} ₽</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-600">Осталось мест</p>
                    <p class="text-2xl font-bold" :class="availableSlots > 0 ? 'text-green-600' : 'text-red-600'">
                      {{ availableSlots }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Book Button -->
              <div>
                <button
                  @click="handleBooking"
                  :disabled="availableSlots === 0"
                  class="w-full py-3 px-4 rounded-lg font-semibold text-white transition-colors"
                  :class="availableSlots > 0
                    ? 'bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
                    : 'bg-gray-400 cursor-not-allowed'"
                >
                  {{ availableSlots > 0 ? 'Записаться' : 'Мест нет' }}
                </button>
              </div>
            </div>
          </div>
        </TransitionChild>
      </div>
    </div>
  </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { TransitionRoot, TransitionChild } from '@headlessui/vue';

const props = defineProps({
  workout: {
    type: Object,
    default: null,
  },
  isOpen: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const page = usePage();

const availableSlots = computed(() => {
  if (!props.workout) return 0;
  return props.workout.slots_total - props.workout.slots_booked;
});

const close = () => {
  emit('close');
};

const handleBooking = () => {
  // Check if user is authenticated
  const isAuthenticated = page.props.auth?.user;

  if (!isAuthenticated) {
    // Redirect to login if not authenticated
    router.visit('/login');
  } else {
    // TODO: Implement booking flow in future sprint
    console.log('Booking workout:', props.workout.id);
  }
};

const getInitials = (name) => {
  if (!name) return '??';
  const parts = name.split(' ');
  if (parts.length >= 2) {
    return parts[0][0] + parts[1][0];
  }
  return name.substring(0, 2).toUpperCase();
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
};

const formatTime = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>
