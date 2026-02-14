<template>
  <div class="max-w-3xl space-y-4">
    <h1 class="text-2xl font-semibold">{{ isEdit ? 'Edit Test' : 'Create Test' }}</h1>

    <form class="space-y-4 rounded bg-white p-6 shadow" @submit.prevent="submit">
      <input v-model="form.title" class="w-full rounded border px-3 py-2" placeholder="Test title" />
      <textarea v-model="form.description" class="w-full rounded border px-3 py-2" rows="4" placeholder="Description" />
      <textarea v-model="form.instructions" class="w-full rounded border px-3 py-2" rows="4" placeholder="Instructions" />
      <div class="grid gap-4 md:grid-cols-2">
        <input v-model.number="form.duration_minutes" type="number" min="0" class="rounded border px-3 py-2" placeholder="Duration (minutes)" />
        <select v-model="form.status" class="rounded border px-3 py-2">
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="archived">Archived</option>
        </select>
      </div>

      <div class="flex justify-end">
        <button class="rounded bg-indigo-600 px-4 py-2 text-white" :disabled="loading">
          {{ loading ? 'Saving...' : 'Save Test' }}
        </button>
      </div>
    </form>

    <div v-if="isEdit" class="rounded border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
      <p class="font-medium">Need to add sections and attach questions?</p>
      <p class="mt-1">
        Open the section builder to add section titles and attach questions to this test.
      </p>
      <router-link :to="`/tests/${route.params.id}`" class="mt-3 inline-block rounded bg-indigo-600 px-3 py-2 text-white">
        Open Section Builder
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../utils/axios';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const isEdit = computed(() => Boolean(route.params.id));

const form = ref({
  title: '',
  description: '',
  instructions: '',
  duration_minutes: 60,
  status: 'draft',
});

onMounted(async () => {
  if (!isEdit.value) return;
  const response = await api.get(`/tests/${route.params.id}`);
  const data = response.data.data ?? response.data;
  form.value = { ...form.value, ...data };
});

async function submit() {
  loading.value = true;
  try {
    if (isEdit.value) {
      await api.put(`/tests/${route.params.id}`, form.value);
      await router.push(`/tests/${route.params.id}`);
      return;
    } else {
      const response = await api.post('/tests', form.value);
      const testId = response.data.data?.id ?? response.data.id;
      if (testId) {
        await router.push(`/tests/${testId}`);
        return;
      }
    }
    await router.push('/tests');
  } finally {
    loading.value = false;
  }
}
</script>
