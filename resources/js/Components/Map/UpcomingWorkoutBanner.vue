<template>
  <Transition name="banner">
    <div
      v-if="sortedWorkouts.length > 0"
      class="absolute top-[4.5rem] md:top-4 inset-x-0 md:left-1/2 md:right-auto md:-translate-x-1/2 z-[1050] pointer-events-none"
    >
      <div class="mx-3 md:mx-0 md:w-[28rem] pointer-events-auto">
        <div
          class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-100 overflow-hidden transition-all duration-300"
        >
          <!-- Compact row -->
          <button
            type="button"
            class="w-full flex items-center gap-3 px-3.5 py-2.5 text-left"
            @click="toggle"
          >
            <div
              class="size-9 rounded-xl flex items-center justify-center shrink-0"
              :class="isStartingSoon ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-500'"
            >
              <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium truncate" :class="isStartingSoon ? 'text-primary-600' : 'text-gray-500'">
                {{ timeLabel }}
              </p>
              <p class="text-sm font-semibold text-gray-900 truncate">
                {{ current.sport_name }} · {{ current.location_name }}
              </p>
            </div>

            <span class="text-sm font-bold text-primary-600 shrink-0">
              {{ formatPrice(current.slot_price) }} ₽
            </span>

            <svg
              class="size-4 text-gray-400 shrink-0 transition-transform duration-200"
              :class="{ 'rotate-180': expanded }"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
          </button>

          <!-- Success message after booking -->
          <Transition name="success-fade">
            <div v-if="showSuccess" class="border-t border-green-100 bg-green-50 px-3.5 py-3">
              <div class="flex items-center gap-2.5">
                <div class="size-8 rounded-full bg-green-500 flex items-center justify-center shrink-0">
                  <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                </div>
                <p class="text-sm font-semibold text-green-800">Вы успешно записаны!</p>
              </div>
            </div>
          </Transition>

          <!-- Expanded details -->
          <Transition name="expand">
            <div v-if="expanded" class="overflow-hidden">
              <div class="border-t border-gray-100 px-3.5 pb-3.5 pt-3 space-y-3">
                <!-- Details grid -->
                <div class="grid grid-cols-2 gap-2 text-sm">
                  <div class="flex items-center gap-1.5 text-gray-600">
                    <svg class="size-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    {{ formatWorkoutDate(current.starts_at) }}
                  </div>
                  <div class="flex items-center gap-1.5 text-gray-600">
                    <svg class="size-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ formatWorkoutTime(current.starts_at) }}
                    <span v-if="current.duration_minutes"> · {{ current.duration_minutes }} мин.</span>
                  </div>
                  <div class="flex items-center gap-1.5 text-gray-600">
                    <svg class="size-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <span class="truncate">{{ current.coach_name }}</span>
                  </div>
                  <div class="flex items-center gap-1.5 text-gray-600">
                    <svg class="size-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    {{ availabilityLabel(current) }}
                  </div>
                </div>

                <!-- Actions row -->
                <div class="flex items-center gap-2">
                  <button
                    @click="goToWorkout"
                    class="flex-1 py-2 rounded-xl text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 active:bg-primary-700 transition-colors"
                  >
                    Показать на карте
                  </button>
                </div>

                <!-- Pagination -->
                <div v-if="sortedWorkouts.length > 1" class="flex items-center justify-between pt-1">
                  <button
                    :disabled="currentIndex === 0"
                    class="flex items-center gap-1 text-xs font-medium transition-colors"
                    :class="currentIndex === 0 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-500 hover:text-gray-700'"
                    @click="prev"
                  >
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    Раньше
                  </button>

                  <span class="text-xs text-gray-400">
                    {{ currentIndex + 1 }} из {{ sortedWorkouts.length }}
                  </span>

                  <button
                    :disabled="currentIndex >= sortedWorkouts.length - 1"
                    class="flex items-center gap-1 text-xs font-medium transition-colors"
                    :class="currentIndex >= sortedWorkouts.length - 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-500 hover:text-gray-700'"
                    @click="next"
                  >
                    Позже
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import {
  formatWorkoutTime,
  formatWorkoutDate,
  formatPrice,
  availabilityLabel,
} from '@/utils/workout';

const props = defineProps({
  workouts: { type: Array, default: () => [] },
  highlightWorkoutId: { type: [Number, String], default: null },
});

const emit = defineEmits(['select']);

const expanded = ref(false);
const currentIndex = ref(0);
const showSuccess = ref(false);
let successTimer = null;

const sortedWorkouts = computed(() => {
  return [...props.workouts]
    .filter(w => new Date(w.starts_at) > new Date())
    .sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at));
});

const current = computed(() => sortedWorkouts.value[currentIndex.value] || null);

const isStartingSoon = computed(() => {
  if (!current.value) return false;
  const diff = new Date(current.value.starts_at) - new Date();
  return diff > 0 && diff < 2 * 60 * 60 * 1000;
});

const timeLabel = computed(() => {
  if (!current.value) return '';
  const diff = new Date(current.value.starts_at) - new Date();
  if (diff <= 0) return 'Идёт сейчас';

  const minutes = Math.floor(diff / 60000);
  if (minutes < 60) return `Через ${minutes} мин.`;

  const hours = Math.floor(minutes / 60);
  if (hours < 24) {
    const remainMinutes = minutes % 60;
    if (remainMinutes === 0) return `Через ${hours} ч.`;
    return `Через ${hours} ч. ${remainMinutes} мин.`;
  }

  const days = Math.floor(hours / 24);
  if (days === 1) return 'Завтра';
  if (days < 7) return `Через ${days} дн.`;
  return formatWorkoutDate(current.value.starts_at);
});

watch(sortedWorkouts, () => {
  if (currentIndex.value >= sortedWorkouts.value.length) {
    currentIndex.value = Math.max(0, sortedWorkouts.value.length - 1);
  }
});

watch(
  () => [sortedWorkouts.value, props.highlightWorkoutId],
  ([workoutList, highlightId]) => {
    if (!highlightId || workoutList.length === 0) return;

    const idx = workoutList.findIndex(w => String(w.id) === String(highlightId));
    if (idx === -1) return;

    currentIndex.value = idx;
    expanded.value = true;
    showSuccess.value = true;

    if (successTimer) clearTimeout(successTimer);
    successTimer = setTimeout(() => {
      showSuccess.value = false;
    }, 4000);
  },
  { immediate: true },
);

onBeforeUnmount(() => {
  if (successTimer) clearTimeout(successTimer);
});

const toggle = () => {
  expanded.value = !expanded.value;
};

const prev = () => {
  if (currentIndex.value > 0) currentIndex.value--;
};

const next = () => {
  if (currentIndex.value < sortedWorkouts.value.length - 1) currentIndex.value++;
};

const goToWorkout = () => {
  if (current.value) {
    emit('select', current.value);
    expanded.value = false;
  }
};
</script>

<style scoped>
.banner-enter-active {
  animation: banner-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.banner-leave-active {
  animation: banner-out 0.25s ease-in both;
}

@keyframes banner-in {
  0% {
    opacity: 0;
    transform: translateY(-12px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}
@keyframes banner-out {
  0% {
    opacity: 1;
    transform: translateY(0);
  }
  100% {
    opacity: 0;
    transform: translateY(-12px);
  }
}

.expand-enter-active {
  animation: expand-open 0.35s cubic-bezier(0.25, 1, 0.5, 1) both;
}
.expand-leave-active {
  animation: expand-close 0.25s ease-in both;
}

@keyframes expand-open {
  0% {
    opacity: 0;
    max-height: 0;
  }
  40% {
    opacity: 1;
  }
  100% {
    opacity: 1;
    max-height: 20rem;
  }
}
@keyframes expand-close {
  0% {
    opacity: 1;
    max-height: 20rem;
  }
  60% {
    opacity: 0;
  }
  100% {
    opacity: 0;
    max-height: 0;
  }
}

.success-fade-enter-active {
  animation: success-in 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.success-fade-leave-active {
  animation: success-out 0.3s ease-in both;
}

@keyframes success-in {
  0% {
    opacity: 0;
    max-height: 0;
  }
  100% {
    opacity: 1;
    max-height: 4rem;
  }
}
@keyframes success-out {
  0% {
    opacity: 1;
    max-height: 4rem;
  }
  100% {
    opacity: 0;
    max-height: 0;
  }
}
</style>
