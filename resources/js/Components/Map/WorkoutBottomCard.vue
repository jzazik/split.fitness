<template>
  <Transition name="card">
    <div
      v-if="isOpen && workout"
      ref="cardEl"
      class="card-container absolute z-[1000] bottom-[calc(4rem+env(safe-area-inset-bottom,0px))] left-3 right-3 sm:left-1/2 sm:right-auto sm:w-full sm:max-w-lg sm:bottom-6 pointer-events-auto"
      :style="dragStyle"
      @touchstart.passive="onTouchStart"
      @touchend="onTouchEnd"
    >
      <button
        @click="close"
        class="absolute -top-2.5 -right-2.5 sm:-top-3 sm:-right-3 size-7 hidden sm:flex items-center justify-center rounded-full bg-white text-gray-400 hover:text-gray-600 shadow-md ring-1 ring-gray-200 transition-colors z-20"
        aria-label="Закрыть"
      >
        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <div class="bg-white rounded-2xl shadow-xl relative overflow-hidden ring-1" :class="ringColorClass">
        <div class="flex justify-center pt-2 pb-0.5 cursor-grab sm:hidden">
          <div class="w-10 h-1 rounded-full bg-gray-300" />
        </div>

        <!-- Compact preview (always visible) -->
        <div class="p-4">
          <div class="flex gap-3">
            <!-- Coach avatar with rating -->
            <div class="flex flex-col items-center shrink-0">
              <div class="relative">
                <img
                  v-if="workout.coach_avatar_url"
                  :src="workout.coach_avatar_url"
                  :alt="workout.coach_name"
                  class="size-16 rounded-full object-cover ring-2 ring-white"
                />
                <div
                  v-else
                  class="size-16 rounded-full bg-primary-100 flex items-center justify-center ring-2 ring-white"
                >
                  <span class="text-primary-600 font-semibold text-lg">
                    {{ getInitials(workout.coach_name) }}
                  </span>
                </div>
                <span
                  v-if="workout.coach_rating != null"
                  class="absolute -bottom-1 left-1/2 -translate-x-1/2 bg-white rounded-full px-1.5 py-0.5 text-xs font-semibold text-gray-800 shadow-sm border border-gray-200 flex items-center gap-0.5"
                >
                  <svg class="size-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  {{ Number(workout.coach_rating).toFixed(1) }}
                </span>
              </div>
              <p class="mt-2 text-xs font-medium text-gray-900 text-center leading-tight max-w-[72px] truncate">
                {{ coachShortName }}
              </p>
            </div>

            <!-- Workout info + price/button -->
            <div class="flex-1 min-w-0 pt-0.5">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ workout.location_name }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 whitespace-nowrap">
                      {{ workout.sport_name }}
                    </span>
                  </div>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ formatWorkoutTime(workout.starts_at) }}
                    <span v-if="workout.duration_minutes" class="mx-0.5">|</span>
                    <span v-if="workout.duration_minutes">{{ workout.duration_minutes }} мин.</span>
                  </p>
                </div>
                <p class="text-base font-bold text-primary-600 whitespace-nowrap shrink-0">
                  {{ formatPrice(workout.slot_price) }} ₽
                </p>
              </div>

              <!-- Availability bars + booking button -->
              <div class="mt-2 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                  <div class="flex gap-0.5 shrink-0">
                    <span
                      v-for="i in slotsTotal"
                      :key="i"
                      class="w-1.5 h-4 rounded-sm"
                      :class="i <= slotsBooked ? 'bg-primary-500' : 'bg-gray-200'"
                    />
                  </div>
                  <span class="text-xs text-gray-500 truncate">
                    {{ availabilityText }}
                  </span>
                </div>
                <button
                  v-if="!expanded"
                  @click="expand"
                  :disabled="availableSlotsCount === 0"
                  class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors whitespace-nowrap shrink-0"
                  :class="availableSlotsCount > 0
                    ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                    : 'bg-gray-400 cursor-not-allowed'"
                >
                  {{ availableSlotsCount > 0 ? 'Записаться' : 'Мест нет' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Expanded booking section -->
        <Transition name="expand">
          <div v-if="expanded" class="overflow-hidden">
            <div class="border-t border-gray-100 px-4 pb-4 pt-3">
              <!-- Extended info -->
              <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                <div class="flex items-center gap-1.5">
                  <svg class="size-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                  </svg>
                  <span>{{ workoutDate }}</span>
                </div>
                <div v-if="workout.coach_name" class="flex items-center gap-1.5">
                  <svg class="size-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                  </svg>
                  <span>{{ workout.coach_name }}</span>
                </div>
              </div>

              <p v-if="workout.description" class="text-sm text-gray-500 mb-4 line-clamp-2">
                {{ workout.description }}
              </p>

              <!-- Guest: inline SMS auth flow -->
              <div v-if="!isAuthenticated">

                <!-- Step 1: Phone input -->
                <form v-if="sms.step.value === 'phone'" @submit.prevent="sms.sendCode()">
                  <label for="booking-phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Введите номер телефона для записи
                  </label>
                  <div class="flex gap-2 items-start">
                    <div class="flex-1">
                      <Input
                        id="booking-phone"
                        type="tel"
                        v-model="sms.phone.value"
                        :error="sms.errors.value.phone?.[0]"
                        autocomplete="tel"
                        inputmode="numeric"
                        placeholder="+7"
                        @input="sms.onPhoneInput()"
                      />
                      <p v-if="!sms.errors.value.phone?.[0]" class="mt-1 text-xs text-gray-400">Отправим SMS с кодом подтверждения</p>
                    </div>
                    <button
                      type="submit"
                      :disabled="!sms.isPhoneValid.value || sms.loading.value || sms.cooldown.value > 0"
                      class="px-6 py-2 border border-transparent rounded-md text-base font-semibold text-white transition-all whitespace-nowrap flex items-center gap-2 shrink-0"
                      :class="sms.isPhoneValid.value && !sms.loading.value && sms.cooldown.value <= 0
                        ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                        : 'bg-gray-300 cursor-not-allowed'"
                    >
                      <svg v-if="sms.loading.value" class="size-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      {{ sms.cooldown.value > 0 ? `${sms.cooldown.value}с` : 'Записаться' }}
                    </button>
                  </div>
                </form>

                <!-- Step 2: Code input -->
                <form v-else-if="sms.step.value === 'code'" @submit.prevent="sms.verifyCode()">
                  <p class="text-sm text-gray-600 mb-3">
                    Код отправлен на <span class="font-medium">{{ sms.phoneFormatted.value }}</span>
                  </p>
                  <div class="flex gap-2 items-start">
                    <div class="flex-1">
                      <Input
                        id="booking-code"
                        type="text"
                        v-model="sms.code.value"
                        :error="sms.errors.value.code?.[0]"
                        autofocus
                        autocomplete="one-time-code"
                        placeholder="123321"
                        inputmode="numeric"
                        maxlength="6"
                      />
                    </div>
                    <button
                      type="submit"
                      :disabled="sms.code.value.length !== 6 || sms.loading.value"
                      class="px-6 py-2 rounded-md text-base font-semibold text-white transition-all whitespace-nowrap flex items-center gap-2 shrink-0"
                      :class="sms.code.value.length === 6 && !sms.loading.value
                        ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                        : 'bg-gray-300 cursor-not-allowed'"
                    >
                      <svg v-if="sms.loading.value" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      Подтвердить
                    </button>
                  </div>
                  <div class="mt-2 flex items-center justify-between">
                    <button type="button" @click="sms.goBack()" class="text-xs text-gray-500 hover:text-gray-700">
                      Изменить номер
                    </button>
                    <button
                      type="button"
                      @click="sms.sendCode()"
                      :disabled="sms.cooldown.value > 0"
                      class="text-xs text-primary-600 hover:text-primary-700 disabled:text-gray-400 disabled:cursor-not-allowed"
                    >
                      {{ sms.cooldown.value > 0 ? `Повторить через ${sms.cooldown.value}с` : 'Отправить повторно' }}
                    </button>
                  </div>
                </form>
              </div>

              <!-- Authenticated user -->
              <div v-else>
                <button
                  @click="handleBooking"
                  :disabled="submitting"
                  class="w-full py-2.5 rounded-lg text-sm font-semibold text-white transition-all flex items-center justify-center gap-2"
                  :class="!submitting
                    ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                    : 'bg-gray-300 cursor-not-allowed'"
                >
                  <svg v-if="submitting" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  Оплатить и подтвердить
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Input from '@/Components/UI/Input.vue';
import { useSmsAuth } from '@/composables/useSmsAuth.js';
import {
  getInitials,
  shortCoachName as buildShortName,
  formatWorkoutTime,
  formatWorkoutDate,
  availableSlots as calcAvailableSlots,
  availabilityLabel as buildAvailabilityLabel,
  formatPrice,
} from '@/utils/workout';

const props = defineProps({
  workout: { type: Object, default: null },
  isOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'update:expanded']);

const page = usePage();

const expanded = ref(false);
const submitting = ref(false);

const sms = useSmsAuth({
  onVerified: () => createBooking(),
});

const SWIPE_DISMISS_THRESHOLD = 80;
const cardEl = ref(null);
const dragOffsetY = ref(0);
const dragging = ref(false);
let touchStartY = 0;

const dragStyle = computed(() => {
  if (!dragging.value || dragOffsetY.value <= 0) return {};
  return { transform: `translateY(${dragOffsetY.value}px)`, transition: 'none' };
});

const onTouchStart = (e) => {
  touchStartY = e.touches[0].clientY;
  dragOffsetY.value = 0;
  dragging.value = false;
};

const onTouchMove = (e) => {
  const deltaY = e.touches[0].clientY - touchStartY;
  if (!dragging.value && deltaY > 0) {
    dragging.value = true;
  }
  if (dragging.value) {
    e.preventDefault();
    dragOffsetY.value = Math.max(0, deltaY);
  }
};

const onTouchEnd = () => {
  if (dragging.value && dragOffsetY.value > SWIPE_DISMISS_THRESHOLD) {
    close();
  }
  dragOffsetY.value = 0;
  dragging.value = false;
};

const bindTouchMove = (el) => {
  if (el) el.addEventListener('touchmove', onTouchMove, { passive: false });
};
const unbindTouchMove = (el) => {
  if (el) el.removeEventListener('touchmove', onTouchMove);
};

watch(cardEl, (newEl, oldEl) => {
  unbindTouchMove(oldEl);
  bindTouchMove(newEl);
});

onBeforeUnmount(() => {
  unbindTouchMove(cardEl.value);
  sms.destroy();
});

const isAuthenticated = computed(() => !!page.props.auth?.user);

const slotsTotal = computed(() => {
  if (!props.workout) return 0;
  return Math.min(props.workout.slots_total || 0, 10);
});

const slotsBooked = computed(() => {
  if (!props.workout) return 0;
  return Math.min(props.workout.slots_booked || 0, slotsTotal.value);
});

const availableSlotsCount = computed(() => props.workout ? calcAvailableSlots(props.workout) : 0);
const availabilityText = computed(() => props.workout ? buildAvailabilityLabel(props.workout) : '');

const ringColorClass = computed(() => {
  if (availableSlotsCount.value <= 0) return 'ring-gray-400';
  const total = props.workout?.slots_total || 0;
  if (total > 0 && availableSlotsCount.value / total < 0.3) return 'ring-amber-400';
  return 'ring-primary-500';
});

const coachShortName = computed(() => buildShortName(props.workout?.coach_name));
const workoutDate = computed(() => props.workout ? formatWorkoutDate(props.workout.starts_at) : '');

watch(() => props.workout, () => { collapse(); });
watch(() => props.isOpen, (open) => { if (!open) collapse(); });

const expand = () => {
  if (availableSlotsCount.value === 0) return;
  expanded.value = true;
  emit('update:expanded', true);
};

const collapse = () => {
  expanded.value = false;
  emit('update:expanded', false);
  sms.reset();
  submitting.value = false;
};

const close = () => {
  collapse();
  emit('close');
};

const createBooking = async () => {
  submitting.value = true;
  try {
    const { data } = await axios.post('/api/bookings', {
      workout_id: props.workout.id,
      slots_count: 1,
    });

    openPaymentWidget(data);
  } catch (error) {
    const msg = error.response?.data?.errors?.workout_id?.[0]
      || error.response?.data?.message
      || 'Не удалось создать бронирование. Попробуйте позже.';
    alert(msg);
    submitting.value = false;
  }
};

function openPaymentWidget(data) {
  const pay = data.payment;
  const bookingId = data.booking.id;

  loadCloudPaymentsScript().then(() => {
    const widget = new window.cp.CloudPayments();
    widget.pay('charge', {
      publicId: pay.public_id,
      description: pay.description,
      amount: pay.amount,
      currency: pay.currency,
      invoiceId: String(pay.invoice_id),
      skin: 'mini',
    }, {
      onSuccess() {
        router.visit('/');
      },
      onFail() {
        alert('Оплата не прошла. Попробуйте ещё раз.');
        submitting.value = false;
      },
      onComplete() {
        submitting.value = false;
      },
    });
  });
}

let cpScriptPromise = null;
function loadCloudPaymentsScript() {
  if (window.cp) return Promise.resolve();
  if (cpScriptPromise) return cpScriptPromise;

  cpScriptPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = 'https://widget.cloudpayments.ru/bundles/cloudpayments.js';
    script.onload = resolve;
    script.onerror = () => reject(new Error('Failed to load CloudPayments widget'));
    document.head.appendChild(script);
  });

  return cpScriptPromise;
}

const handleBooking = async () => {
  if (isAuthenticated.value) {
    await createBooking();
  }
};
</script>

<style scoped>
/* Desktop centering via translate instead of Tailwind class (animation overrides transform) */
.card-container {
  transform: none;
}

@media (min-width: 640px) {
  .card-container {
    transform: translateX(-50%);
  }
}

/* Card enter/leave — mobile */
.card-enter-active {
  animation: card-in 0.45s cubic-bezier(0.22, 1.2, 0.36, 1) both;
}
.card-leave-active {
  animation: card-out 0.25s ease-in both;
}

@keyframes card-in {
  0% {
    opacity: 0;
    transform: translateY(40px) scale(0.96);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes card-out {
  0% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  100% {
    opacity: 0;
    transform: translateY(24px) scale(0.97);
  }
}

/* Card enter/leave — desktop: preserve translateX(-50%) for centering */
@media (min-width: 640px) {
  .card-enter-active {
    animation: card-in-desktop 0.45s cubic-bezier(0.22, 1.2, 0.36, 1) both;
  }
  .card-leave-active {
    animation: card-out-desktop 0.25s ease-in both;
  }
}

@keyframes card-in-desktop {
  0% {
    opacity: 0;
    transform: translateX(-50%) translateY(40px) scale(0.96);
  }
  100% {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
}

@keyframes card-out-desktop {
  0% {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
  100% {
    opacity: 0;
    transform: translateX(-50%) translateY(24px) scale(0.97);
  }
}

/* Expanded booking section — grid-based height animation */
.expand-enter-active {
  animation: expand-open 0.8s cubic-bezier(0.25, 1, 0.5, 1) both;
}
.expand-leave-active {
  animation: expand-close 0.6s ease-in both;
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
    max-height: 36rem;
  }
}

@keyframes expand-close {
  0% {
    opacity: 1;
    max-height: 36rem;
  }
  60% {
    opacity: 0;
  }
  100% {
    opacity: 0;
    max-height: 0;
  }
}
</style>
