<template>
  <div>
    <h2>Enter OTP</h2>
    <p>OTP sent to {{ email }}</p>
    <form @submit.prevent="verify">
      <input v-model="otp" type="text" maxlength="6" required />
      <button type="submit">Verify</button>
    </form>
    <button @click="resend">Resend OTP</button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const email = ref(route.query.email || '');
const otp = ref('');

async function verify() {
  try {
    await auth.verifyOtp(email.value, otp.value);
    router.push('/dashboard');
  } catch (error) {
    alert('Invalid OTP');
  }
}

function resend() {
  auth.sendOtp(email.value);
  alert('OTP resent');
}
</script>
