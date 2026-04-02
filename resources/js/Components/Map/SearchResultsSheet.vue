<template>
  <div class="md:hidden">
    <!-- Floating button -->
    <Transition name="fab">
      <button
        v-if="workouts.length > 0 && !isOpen && !selectedWorkout"
        type="button"
        class="fixed z-[1000] bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 px-5 py-3 rounded-full bg-primary-500 text-white text-sm font-semibold shadow-lg active:scale-95 transition-transform whitespace-nowrap"
        @click="isOpen = true"
      >
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        Результаты поиска
        <span class="inline-flex items-center justify-center size-5 rounded-full bg-white/20 text-xs font-bold">
          {{ workouts.length }}
        </span>
      </button>
    </Transition>

    <BottomSheet
      v-model="isOpen"
      title="Результаты поиска"
      :badge="workouts.length"
      closable
      max-height="50vh"
    >
      <div class="overflow-y-auto overscroll-contain px-4 pb-6" :style="{ maxHeight: 'calc(50vh - 72px)' }">
        <div class="space-y-3">
          <button
            v-for="workout in workouts"
            :key="workout.id"
            type="button"
            class="w-full text-left bg-gray-50 rounded-xl p-3 active:bg-gray-100 transition-colors"
            @click="$emit('select', workout)"
          >
            <div class="flex gap-3">
              <div class="flex flex-col items-center shrink-0">
                <div class="relative">
                  <img
                    v-if="workout.coach_avatar_url"
                    :src="workout.coach_avatar_url"
                    :alt="workout.coach_name"
                    class="size-12 rounded-full object-cover ring-2 ring-white"
                  />
                  <div
                    v-else
                    class="size-12 rounded-full bg-primary-100 flex items-center justify-center ring-2 ring-white"
                  >
                    <span class="text-primary-600 font-semibold text-sm">
                      {{ getInitials(workout.coach_name) }}
                    </span>
                  </div>
                  <span
                    v-if="workout.coach_rating != null"
                    class="absolute -bottom-1 left-1/2 -translate-x-1/2 bg-white rounded-full px-1 py-px text-[10px] font-semibold text-gray-800 shadow-sm border border-gray-200 flex items-center gap-0.5"
                  >
                    <svg class="size-2.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    {{ Number(workout.coach_rating).toFixed(1) }}
                  </span>
                </div>
              </div>

              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ workout.location_name }}</p>
                  <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600 whitespace-nowrap shrink-0">
                    {{ workout.sport_name }}
                  </span>
                </div>

                <p class="mt-0.5 text-xs text-gray-500">
                  {{ shortCoachName(workout.coach_name) }}
                  <span class="mx-0.5">·</span>
                  {{ formatWorkoutTime(workout.starts_at) }}
                  <span v-if="workout.duration_minutes" class="mx-0.5">·</span>
                  <span v-if="workout.duration_minutes">{{ workout.duration_minutes }} мин.</span>
                </p>

                <div class="mt-1.5 flex items-center gap-2">
                  <div class="flex gap-0.5">
                    <span
                      v-for="i in slotsBars(workout)"
                      :key="i"
                      class="w-1 h-3 rounded-sm"
                      :class="i <= (workout.slots_booked || 0) ? 'bg-primary-500' : 'bg-gray-200'"
                    />
                  </div>
                  <span class="text-[10px] text-gray-500 whitespace-nowrap">
                    {{ availabilityLabel(workout) }}
                  </span>
                </div>
              </div>

              <div class="flex flex-col items-end justify-between shrink-0 pl-1">
                <p class="text-sm font-bold text-primary-600 whitespace-nowrap">
                  {{ formatPrice(workout.slot_price) }} ₽
                </p>
                <span
                  class="mt-1 text-[10px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap"
                  :class="availableSlots(workout) > 0
                    ? 'bg-green-50 text-green-700'
                    : 'bg-gray-100 text-gray-500'"
                >
                  {{ availableSlots(workout) > 0 ? 'Записаться' : 'Мест нет' }}
                </span>
              </div>
            </div>
          </button>
        </div>
      </div>
    </BottomSheet>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import BottomSheet from '@/Components/UI/BottomSheet.vue';
import { getInitials, shortCoachName, formatWorkoutTime, availableSlots, availabilityLabel, formatPrice } from '@/utils/workout';

defineProps({
  workouts: {
    type: Array,
    default: () => [],
  },
  selectedWorkout: {
    type: Object,
    default: null,
  },
});

defineEmits(['select']);

const isOpen = ref(false);

const close = () => {
  isOpen.value = false;
};

const slotsBars = (workout) => Math.min(workout.slots_total || 0, 10);

defineExpose({ close });
</script>

<style scoped>
.fab-enter-active {
  animation: fab-bounce-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
.fab-leave-active {
  animation: fab-slide-out 0.25s ease-in both;
}

@keyframes fab-bounce-in {
  0% {
    opacity: 0;
    transform: translateX(-50%) translateY(24px) scale(0.8);
  }
  60% {
    opacity: 1;
    transform: translateX(-50%) translateY(-6px) scale(1.04);
  }
  80% {
    transform: translateX(-50%) translateY(2px) scale(0.98);
  }
  100% {
    transform: translateX(-50%) translateY(0) scale(1);
  }
}

@keyframes fab-slide-out {
  0% {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
  100% {
    opacity: 0;
    transform: translateX(-50%) translateY(16px) scale(0.9);
  }
}
</style>
