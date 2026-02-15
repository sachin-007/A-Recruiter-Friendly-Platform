<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Tests</h1>
      <router-link v-if="canCreate" to="/tests/create" class="rounded bg-indigo-600 px-4 py-2 text-white">Create Test</router-link>
    </div>

    <div class="overflow-hidden rounded bg-white shadow">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Title</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Duration</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="test in tests.data" :key="test.id">
            <td class="px-4 py-3">{{ test.title }}</td>
            <td class="px-4 py-3">{{ test.duration_minutes }} min</td>
            <td class="px-4 py-3">{{ test.status }}</td>
            <td class="px-4 py-3 text-right space-x-3">
              <router-link :to="`/tests/${test.id}`" class="text-indigo-600">Manage Sections</router-link>
              <router-link v-if="canCreate" :to="`/tests/${test.id}/edit`" class="text-indigo-600">Edit Meta</router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../utils/axios';

const auth = useAuthStore();
const tests = ref({ data: [] });
const canCreate = computed(() => ['super_admin', 'admin', 'author', 'recruiter'].includes(auth.role));

onMounted(async () => {
  const response = await api.get('/tests');
  tests.value = response.data;
});
</script>
