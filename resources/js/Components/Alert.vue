<template>
  <div v-if="alert.message" :class="`alert alert-${alert.type}`" class="mb-4 p-4 rounded">
    {{ alert.message }}
    <button @click="alert.clear()" class="float-right">&times;</button>
  </div>
</template>

<script setup>
import { useAlertStore } from '../stores/alert';

const alert = useAlertStore();
</script>