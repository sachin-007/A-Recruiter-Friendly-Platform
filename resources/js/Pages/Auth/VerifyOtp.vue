<template>
  <div class="auth-shell">
    <div class="auth-panel">
      <div class="space-y-1">
        <p class="auth-eyebrow">Verification</p>
        <h1 class="auth-title">Enter OTP</h1>
        <p class="auth-subtitle">
          We sent a 6-digit code to <strong>{{ email || 'your email' }}</strong>.
        </p>
      </div>

      <form class="mt-6 space-y-4" @submit.prevent="verify">
        <div>
          <label class="field-label">One-time password</label>
          <input
            v-model.trim="otp"
            maxlength="6"
            minlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            required
            class="field-input tracking-[0.35em]"
            placeholder="123456"
          />
        </div>

        <button type="submit" class="btn-primary w-full" :disabled="loading || otp.length !== 6">
          {{ loading ? 'Verifying...' : 'Verify & Continue' }}
        </button>
      </form>

      <div class="mt-4 flex items-center justify-between text-sm">
        <router-link to="/login" class="font-medium text-slate-600 hover:text-slate-900">Change email</router-link>
        <button
          class="font-medium text-indigo-600 disabled:text-slate-400"
          :disabled="resendLoading || cooldown > 0 || !email"
          @click="resend"
        >
          {{ resendLoading ? 'Sending...' : cooldown > 0 ? `Resend in ${cooldown}s` : 'Resend OTP' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useAlertStore } from '../../stores/alert';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const alert = useAlertStore();

const email = ref((route.query.email || '').toString());
const otp = ref('');
const loading = ref(false);
const resendLoading = ref(false);
const cooldown = ref(0);
let cooldownTimer = null;

onMounted(() => {
  if (!email.value) {
    router.replace('/login');
  }
  startCooldown(30);
});

onBeforeUnmount(() => {
  if (cooldownTimer) {
    clearInterval(cooldownTimer);
  }
});

async function verify() {
  loading.value = true;
  try {
    await auth.verifyOtp(email.value, otp.value);
    router.push('/dashboard');
  } catch (error) {
    const message = error.response?.data?.errors?.otp?.[0]
      || error.response?.data?.message
      || 'Invalid OTP';
    alert.error(message);
  } finally {
    loading.value = false;
  }
}

async function resend() {
  if (!email.value || cooldown.value > 0) return;

  resendLoading.value = true;
  try {
    await auth.sendOtp(email.value);
    alert.success('OTP resent successfully');
    startCooldown(30);
  } catch (error) {
    const message = error.response?.data?.message || 'Unable to resend OTP';
    alert.error(message);
  } finally {
    resendLoading.value = false;
  }
}

function startCooldown(seconds) {
  cooldown.value = seconds;
  if (cooldownTimer) clearInterval(cooldownTimer);
  cooldownTimer = setInterval(() => {
    if (cooldown.value > 0) {
      cooldown.value -= 1;
    } else {
      clearInterval(cooldownTimer);
      cooldownTimer = null;
    }
  }, 1000);
}
</script>
