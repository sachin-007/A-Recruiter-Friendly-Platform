<template>
  <div>
    <div class="fixed inset-x-0 top-3 z-50 mx-auto w-full max-w-3xl px-3">
      <Alert />
    </div>
    <router-view />
  </div>
</template>

<script setup>
import Alert from './Components/Alert.vue';
</script>
