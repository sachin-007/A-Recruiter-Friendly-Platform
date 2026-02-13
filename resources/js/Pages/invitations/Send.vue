<template>
  <div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-semibold mb-6">Send Invitations</h1>
    <div class="bg-white p-6 rounded shadow">
      <form @submit.prevent="submit">
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Test</label>
          <select v-model="form.test_id" required class="w-full border rounded px-3 py-2">
            <option value="">Select a test</option>
            <option v-for="test in tests" :key="test.id" :value="test.id">{{ test.title }}</option>
          </select>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Candidates</label>
          <div v-for="(candidate, index) in form.candidates" :key="index" class="flex space-x-2 mb-2">
            <input v-model="candidate.email" type="email" placeholder="Email" required class="flex-1 border rounded px-3 py-2">
            <input v-model="candidate.name" type="text" placeholder="Name (optional)" class="flex-1 border rounded px-3 py-2">
            <button type="button" @click="removeCandidate(index)" class="text-red-500">Remove</button>
          </div>
          <button type="button" @click="addCandidate" class="text-indigo-600 text-sm mt-2">+ Add another candidate</button>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700" :disabled="loading">
            {{ loading ? 'Sending...' : 'Send Invitations' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const router = useRouter();
const alert = useAlertStore();
const tests = ref([]);
const loading = ref(false);
const form = ref({
  test_id: '',
  candidates: [{ email: '', name: '' }],
});

onMounted(async () => {
  const res = await api.get('/tests?per_page=100');
  tests.value = res.data.data;
});

function addCandidate() {
  form.value.candidates.push({ email: '', name: '' });
}

function removeCandidate(index) {
  form.value.candidates.splice(index, 1);
}

async function submit() {
  loading.value = true;
  try {
    await api.post('/invitations/bulk', form.value);
    alert.success('Invitations sent successfully');
    router.push('/invitations');
  } finally {
    loading.value = false;
  }
}
</script>
