<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full p-6 bg-white rounded-lg shadow">
      <h2 class="text-2xl font-bold mb-6">Login</h2>
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 border rounded-lg"
            placeholder="you@example.com"
          />
        </div>
        <button
          type="submit"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700"
          :disabled="loading"
        >
          {{ loading ? 'Sending...' : 'Send OTP' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';

const email = ref('');
const loading = ref(false);
const auth = useAuthStore();
const router = useRouter();

async function submit() {
  loading.value = true;
  try {
    await auth.sendOtp(email.value);
    router.push({ path: '/verify-otp', query: { email: email.value } });
  } finally {
    loading.value = false;
  }
}
</script>
