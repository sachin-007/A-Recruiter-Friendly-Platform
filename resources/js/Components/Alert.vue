<template>
  <div
    v-if="alert.message"
    :class="[
      'mb-4 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm shadow-sm',
      alertClass,
    ]"
  >
    <p>{{ alert.message }}</p>
    <button @click="alert.clear()" class="font-semibold">x</button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAlertStore } from '../stores/alert';

const alert = useAlertStore();

const alertClass = computed(() => {
  return alert.type === 'danger'
    ? 'border-rose-200 bg-rose-50 text-rose-700'
    : 'border-emerald-200 bg-emerald-50 text-emerald-700';
});
</script>
