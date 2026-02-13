<template>
  <div :class="['font-mono text-xl', { 'text-red-600': secondsLeft < 60 }]">
    {{ formattedTime }}
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  initialSeconds: { type: Number, default: 0 },
});

const emit = defineEmits(['time-end']);

const secondsLeft = ref(props.initialSeconds);
let timer = null;

const formattedTime = computed(() => {
  const hrs = Math.floor(secondsLeft.value / 3600);
  const mins = Math.floor((secondsLeft.value % 3600) / 60);
  const secs = secondsLeft.value % 60;
  return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

onMounted(() => {
  timer = setInterval(() => {
    if (secondsLeft.value > 0) {
      secondsLeft.value--;
    } else {
      clearInterval(timer);
      emit('time-end');
    }
  }, 1000);
});

onUnmounted(() => {
  clearInterval(timer);
});
</script>