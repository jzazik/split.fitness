<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 bg-black/30 z-[1100]"
        @click="close"
        @touchmove.prevent
      />
    </Transition>

    <Transition name="bottom-sheet">
      <div
        v-if="modelValue"
        ref="sheetEl"
        class="fixed z-[1101] bg-white shadow-xl overflow-y-auto overscroll-none"
        :class="sheetPositionClass"
        :style="[{ maxHeight: maxHeight }, dragStyle]"
        @touchstart.passive="onTouchStart"
        @touchend="onTouchEnd"
      >
        <div class="sticky top-0 z-10 bg-white">
          <div class="flex justify-center pt-3 pb-1 cursor-grab">
            <div class="w-10 h-1 rounded-full bg-gray-300" />
          </div>

          <div v-if="title || $slots.header || closable" class="flex items-center justify-between px-4 pb-3">
            <slot name="header">
              <h3 v-if="title" class="text-base font-semibold text-gray-900">
                {{ title }}
                <span v-if="badge != null" class="text-sm font-normal text-gray-500 ml-1">{{ badge }}</span>
              </h3>
            </slot>
            <button
              v-if="closable"
              type="button"
              class="size-8 flex items-center justify-center rounded-full text-gray-400 active:bg-gray-100 transition-colors"
              aria-label="Закрыть"
              @click="close"
            >
              <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <slot />
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';

const SWIPE_DISMISS_THRESHOLD = 80;

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: null },
  badge: { type: [String, Number], default: null },
  closable: { type: Boolean, default: false },
  maxHeight: { type: String, default: '70vh' },
  position: {
    type: String,
    default: 'center',
    validator: (v) => ['center', 'bottom'].includes(v),
  },
});

const emit = defineEmits(['update:modelValue']);

const sheetEl = ref(null);
const dragOffsetY = ref(0);
const dragging = ref(false);
let touchStartY = 0;
let touchStartScrollTop = 0;

const sheetPositionClass = computed(() =>
  props.position === 'bottom'
    ? 'inset-x-0 bottom-0 rounded-t-2xl'
    : 'inset-x-3 bottom-3 rounded-2xl'
);

const dragStyle = computed(() => {
  if (!dragging.value || dragOffsetY.value <= 0) return {};
  return { transform: `translateY(${dragOffsetY.value}px)`, transition: 'none' };
});

const close = () => {
  emit('update:modelValue', false);
};

const onTouchStart = (e) => {
  const el = sheetEl.value;
  if (!el) return;
  touchStartY = e.touches[0].clientY;
  touchStartScrollTop = el.scrollTop;
  dragOffsetY.value = 0;
  dragging.value = false;
};

const onTouchMove = (e) => {
  const el = sheetEl.value;
  if (!el) return;
  const deltaY = e.touches[0].clientY - touchStartY;

  if (!dragging.value && touchStartScrollTop <= 0 && deltaY > 0) {
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

watch(sheetEl, (newEl, oldEl) => {
  unbindTouchMove(oldEl);
  bindTouchMove(newEl);
});

onBeforeUnmount(() => {
  unbindTouchMove(sheetEl.value);
});

defineExpose({ close });
</script>

<style scoped>
.bottom-sheet-enter-active {
  transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.3s ease-out;
}
.bottom-sheet-leave-active {
  transition: transform 0.2s ease-in, opacity 0.2s ease-in;
}
.bottom-sheet-enter-from,
.bottom-sheet-leave-to {
  opacity: 0;
  transform: translateY(100%);
}
</style>
