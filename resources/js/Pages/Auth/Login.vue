<template>
  <div class="auth-shell">
    <div class="auth-panel">
      <div class="space-y-1">
        <p class="auth-eyebrow">Secure access</p>
        <h1 class="auth-title">Sign in with OTP</h1>
        <p class="auth-subtitle">
          Enter your work email. We will send a one-time code valid for 10 minutes.
        </p>
      </div>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
          <label class="field-label">Email address</label>
          <input
            v-model.trim="email"
            type="email"
            required
            autocomplete="email"
            class="field-input"
            placeholder="you@company.com"
          />
        </div>

        <button type="submit" class="btn-primary w-full" :disabled="loading">
          {{ loading ? 'Sending OTP...' : 'Send OTP' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useAlertStore } from '../../stores/alert';

const email = ref('');
const loading = ref(false);
const auth = useAuthStore();
const alert = useAlertStore();
const router = useRouter();

async function submit() {
  loading.value = true;
  try {
    await auth.sendOtp(email.value);
    alert.success('OTP sent. Check your inbox.');
    router.push({ path: '/verify-otp', query: { email: email.value } });
  } catch (error) {
    const message = error.response?.data?.message || 'Unable to send OTP';
    alert.error(message);
  } finally {
    loading.value = false;
  }
}
</script>
