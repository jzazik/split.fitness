<template>
  <div class="flex items-center justify-center" :class="containerClass">
    <div class="text-center">
      <div
        class="inline-block animate-spin rounded-full border-4 border-solid border-primary-600 border-r-transparent"
        :class="sizeClass"
      ></div>
      <p v-if="message" class="mt-2 text-sm text-gray-600">{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  message: {
    type: String,
    default: '',
  },
  containerClass: {
    type: String,
    default: '',
  },
});

const sizeClass = computed(() => {
  const sizes = {
    sm: 'h-5 w-5',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
  };
  return sizes[props.size];
});
</script>
