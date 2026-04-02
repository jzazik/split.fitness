<template>
  <Transition name="card">
    <div
      v-if="isOpen && workout"
      ref="cardContainer"
      class="card-container z-50 left-3 right-3 sm:left-1/2 sm:right-auto sm:w-full sm:max-w-lg pointer-events-auto"
      :class="showsInputForm
        ? 'fixed bottom-auto top-[env(safe-area-inset-top,0px)] sm:absolute sm:top-auto sm:bottom-6'
        : 'absolute bottom-6'"
    >
      <button
        @click="close"
        class="absolute -top-2.5 -right-2.5 sm:-top-3 sm:-right-3 size-7 flex items-center justify-center rounded-full bg-white text-gray-400 hover:text-gray-600 shadow-md ring-1 ring-gray-200 transition-colors z-20"
        aria-label="Закрыть"
      >
        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <div class="bg-white rounded-2xl shadow-xl relative overflow-hidden">

        <!-- Compact preview: condensed single-line when input form is shown on mobile -->
        <div v-if="showsInputForm" class="px-4 py-3 flex items-center gap-3">
          <p class="text-sm font-medium text-gray-900 truncate flex-1">
            {{ workout.sport_name }} · {{ workout.location_name }}
          </p>
          <p class="text-sm font-bold text-primary-600 whitespace-nowrap shrink-0">
            {{ formatPrice(workout.slot_price) }} ₽
          </p>
        </div>

        <!-- Compact preview: full card when not in input mode -->
        <div v-else class="p-4">
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

            <!-- Workout info -->
            <div class="flex-1 min-w-0 pt-0.5">
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

              <!-- Availability bars -->
              <div class="mt-2 flex items-center gap-2">
                <div class="flex gap-0.5">
                  <span
                    v-for="i in slotsTotal"
                    :key="i"
                    class="w-1.5 h-4 rounded-sm"
                    :class="i <= slotsBooked ? 'bg-primary-500' : 'bg-gray-200'"
                  />
                </div>
                <span class="text-xs text-gray-500 whitespace-nowrap">
                  {{ availabilityText }}
                </span>
              </div>
            </div>

            <!-- Price & booking button -->
            <div class="flex flex-col items-end justify-between shrink-0 pl-2">
              <p class="text-base font-bold text-primary-600 whitespace-nowrap">
                {{ formatPrice(workout.slot_price) }} ₽
              </p>
              <button
                v-if="!expanded"
                @click="expand"
                :disabled="availableSlotsCount === 0"
                class="mt-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors whitespace-nowrap"
                :class="availableSlotsCount > 0
                  ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                  : 'bg-gray-400 cursor-not-allowed'"
              >
                {{ availableSlotsCount > 0 ? 'Записаться' : 'Мест нет' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Expanded booking section -->
        <Transition name="expand">
          <div v-if="expanded" class="overflow-hidden">
            <div class="px-4 pb-4" :class="showsInputForm ? 'pt-0' : 'border-t border-gray-100 pt-3'">
              <!-- Extended info (hidden when input form is shown on mobile) -->
              <template v-if="!showsInputForm">
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
              </template>

              <!-- Guest: inline SMS auth flow -->
              <div v-if="!isAuthenticated">

                <!-- Step 1: Phone input -->
                <div v-if="authStep === 'phone'">
                  <label for="booking-phone" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Введите номер телефона для записи
                  </label>
                  <div class="flex gap-2">
                    <div class="relative flex-1">
                      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-sm pointer-events-none">+7</span>
                      <input
                        ref="phoneInput"
                        id="booking-phone"
                        v-model="phone"
                        type="tel"
                        inputmode="numeric"
                        placeholder="900 123-45-67"
                        maxlength="13"
                        class="w-full rounded-lg border-gray-300 pl-9 pr-3 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500 transition-colors"
                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': phoneError }"
                        @input="onPhoneInput"
                        @keydown.enter="sendSmsCode"
                      />
                    </div>
                    <button
                      @click="sendSmsCode"
                      :disabled="!isPhoneValid || submitting"
                      class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition-all whitespace-nowrap flex items-center gap-2"
                      :class="isPhoneValid && !submitting
                        ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                        : 'bg-gray-300 cursor-not-allowed'"
                    >
                      <svg v-if="submitting" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      Записаться
                    </button>
                  </div>
                  <p v-if="phoneError" class="mt-1.5 text-xs text-red-500">{{ phoneError }}</p>
                  <p v-else class="mt-1.5 text-xs text-gray-400">Отправим SMS с кодом подтверждения</p>
                </div>

                <!-- Step 2: Code input -->
                <div v-else-if="authStep === 'code'">
                  <p class="text-sm text-gray-600 mb-3">
                    Код отправлен на <span class="font-medium">+7 {{ formatPhoneValue(rawPhone) }}</span>
                  </p>
                  <div class="flex gap-2">
                    <input
                      ref="codeInput"
                      v-model="smsCode"
                      type="text"
                      inputmode="numeric"
                      placeholder="123321"
                      maxlength="6"
                      autocomplete="one-time-code"
                      class="flex-1 rounded-lg border-gray-300 px-3 py-2.5 text-sm text-center tracking-widest font-medium focus:border-primary-500 focus:ring-primary-500 transition-colors"
                      :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': codeError }"
                      @keydown.enter="verifySmsCode"
                    />
                    <button
                      @click="verifySmsCode"
                      :disabled="smsCode.length !== 6 || submitting"
                      class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white transition-all whitespace-nowrap flex items-center gap-2"
                      :class="smsCode.length === 6 && !submitting
                        ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                        : 'bg-gray-300 cursor-not-allowed'"
                    >
                      <svg v-if="submitting" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      Подтвердить
                    </button>
                  </div>
                  <p v-if="codeError" class="mt-1.5 text-xs text-red-500">{{ codeError }}</p>
                  <div class="mt-2 flex items-center justify-between">
                    <button @click="authStep = 'phone'; smsCode = ''; codeError = ''" class="text-xs text-gray-500 hover:text-gray-700">
                      Изменить номер
                    </button>
                    <button
                      @click="resendCode"
                      :disabled="cooldown > 0"
                      class="text-xs text-primary-600 hover:text-primary-700 disabled:text-gray-400 disabled:cursor-not-allowed"
                    >
                      {{ cooldown > 0 ? `Повторить через ${cooldown}с` : 'Отправить повторно' }}
                    </button>
                  </div>
                </div>

                <!-- Step 3: Quick registration (new user) -->
                <div v-else-if="authStep === 'register'">
                  <p class="text-sm text-gray-600 mb-3">
                    Номер подтверждён. Заполните данные для записи:
                  </p>
                  <div class="space-y-3">
                    <input
                      v-model="regForm.first_name"
                      type="text"
                      placeholder="Имя"
                      autocomplete="given-name"
                      class="w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500"
                      :class="{ 'border-red-300': regErrors.first_name }"
                    />
                    <p v-if="regErrors.first_name" class="text-xs text-red-500 -mt-2">{{ regErrors.first_name[0] }}</p>

                    <input
                      v-model="regForm.last_name"
                      type="text"
                      placeholder="Фамилия"
                      autocomplete="family-name"
                      class="w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500"
                      :class="{ 'border-red-300': regErrors.last_name }"
                    />
                    <p v-if="regErrors.last_name" class="text-xs text-red-500 -mt-2">{{ regErrors.last_name[0] }}</p>

                    <button
                      @click="registerAndBook"
                      :disabled="submitting || !regForm.first_name || !regForm.last_name"
                      class="w-full py-2.5 rounded-lg text-sm font-semibold text-white transition-all flex items-center justify-center gap-2"
                      :class="!submitting && regForm.first_name && regForm.last_name
                        ? 'bg-primary-500 hover:bg-primary-600 active:bg-primary-700'
                        : 'bg-gray-300 cursor-not-allowed'"
                    >
                      <svg v-if="submitting" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                      </svg>
                      Записаться
                    </button>
                  </div>
                </div>
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
                  Подтвердить запись
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
import { ref, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
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
const phoneInput = ref(null);
const codeInput = ref(null);
const cardContainer = ref(null);

// Phone step
const phone = ref('');
const phoneError = ref('');

// SMS auth inline flow: 'phone' → 'code' → 'register'
const authStep = ref('phone');
const smsCode = ref('');
const codeError = ref('');
const cooldown = ref(0);
let cooldownTimer = null;

// Quick registration
const regForm = ref({ first_name: '', last_name: '' });
const regErrors = ref({});

const isAuthenticated = computed(() => !!page.props.auth?.user);

const showsInputForm = computed(() => {
  if (typeof window === 'undefined') return false;
  return expanded.value && !isAuthenticated.value && window.innerWidth < 640;
});

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
const coachShortName = computed(() => buildShortName(props.workout?.coach_name));
const workoutDate = computed(() => props.workout ? formatWorkoutDate(props.workout.starts_at) : '');

const rawPhone = computed(() => phone.value.replace(/\D/g, ''));
const isPhoneValid = computed(() => rawPhone.value.length === 10);
const fullPhone = computed(() => `+7${rawPhone.value}`);

watch(() => props.workout, () => { collapse(); });
watch(() => props.isOpen, (open) => { if (!open) collapse(); });

const formatPhoneValue = (digits) => {
  if (digits.length <= 3) return digits;
  if (digits.length <= 6) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
  if (digits.length <= 8) return `${digits.slice(0, 3)} ${digits.slice(3, 6)}-${digits.slice(6)}`;
  return `${digits.slice(0, 3)} ${digits.slice(3, 6)}-${digits.slice(6, 8)}-${digits.slice(8, 10)}`;
};

const onPhoneInput = () => {
  phoneError.value = '';
  const digits = phone.value.replace(/\D/g, '').slice(0, 10);
  phone.value = formatPhoneValue(digits);
};

function startCooldown(seconds = 60) {
  cooldown.value = seconds;
  clearInterval(cooldownTimer);
  cooldownTimer = setInterval(() => {
    cooldown.value--;
    if (cooldown.value <= 0) clearInterval(cooldownTimer);
  }, 1000);
}

const expand = () => {
  if (availableSlotsCount.value === 0) return;
  expanded.value = true;
  emit('update:expanded', true);
  if (!isAuthenticated.value) {
    nextTick(() => phoneInput.value?.focus());
  }
};

const collapse = () => {
  expanded.value = false;
  emit('update:expanded', false);
  phone.value = '';
  phoneError.value = '';
  smsCode.value = '';
  codeError.value = '';
  authStep.value = 'phone';
  submitting.value = false;
  regForm.value = { first_name: '', last_name: '' };
  regErrors.value = {};
  cooldown.value = 0;
  clearInterval(cooldownTimer);
};

const close = () => {
  collapse();
  emit('close');
};

const sendSmsCode = async () => {
  if (!isPhoneValid.value) {
    phoneError.value = 'Введите 10 цифр номера телефона';
    return;
  }

  submitting.value = true;
  phoneError.value = '';
  try {
    await axios.post(route('auth.sms.send'), { phone: fullPhone.value });
    authStep.value = 'code';
    startCooldown();
    nextTick(() => codeInput.value?.focus());
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors || {};
      if (errors.phone?.[0]?.includes('уже отправлен')) {
        authStep.value = 'code';
        startCooldown();
        nextTick(() => codeInput.value?.focus());
      } else {
        phoneError.value = errors.phone?.[0] || 'Ошибка отправки.';
      }
    } else {
      phoneError.value = 'Не удалось отправить SMS. Попробуйте позже.';
    }
  } finally {
    submitting.value = false;
  }
};

const resendCode = () => {
  if (cooldown.value > 0) return;
  sendSmsCode();
};

const verifySmsCode = async () => {
  if (smsCode.value.length !== 6) return;

  submitting.value = true;
  codeError.value = '';
  try {
    const { data } = await axios.post(route('auth.sms.verify'), {
      phone: fullPhone.value,
      code: smsCode.value,
    });

    if (data.action === 'login') {
      await createBooking();
    } else if (data.action === 'register') {
      authStep.value = 'register';
    }
  } catch (e) {
    if (e.response?.status === 422) {
      codeError.value = e.response.data.errors?.code?.[0] || 'Неверный код.';
    } else {
      codeError.value = 'Произошла ошибка. Попробуйте позже.';
    }
  } finally {
    submitting.value = false;
  }
};

const registerAndBook = async () => {
  submitting.value = true;
  regErrors.value = {};
  try {
    await axios.post(route('auth.sms.register'), {
      phone: fullPhone.value,
      role: 'athlete',
      first_name: regForm.value.first_name,
      last_name: regForm.value.last_name,
    });
    await createBooking();
  } catch (e) {
    if (e.response?.status === 422) {
      regErrors.value = e.response.data.errors || {};
    } else {
      regErrors.value = { first_name: ['Произошла ошибка. Попробуйте позже.'] };
    }
    submitting.value = false;
  }
};

const createBooking = async () => {
  submitting.value = true;
  try {
    const response = await axios.post('/api/bookings', {
      workout_id: props.workout.id,
      slots_count: 1,
    });
    const bookingId = response.data.booking.id;
    router.visit(`/athlete/bookings/${bookingId}`);
  } catch (error) {
    const msg = error.response?.data?.errors?.workout_id?.[0]
      || error.response?.data?.message
      || 'Не удалось создать бронирование. Попробуйте позже.';
    alert(msg);
    submitting.value = false;
  }
};

const handleBooking = async () => {
  if (isAuthenticated.value) {
    const userRole = page.props.auth?.user?.role;
    if (userRole !== 'athlete') {
      alert('Только атлеты могут записываться на тренировки');
      return;
    }
    await createBooking();
  }
};
</script>

<style scoped>
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
