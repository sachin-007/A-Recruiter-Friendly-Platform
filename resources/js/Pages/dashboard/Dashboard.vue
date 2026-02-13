<template>
  <div>
    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <div v-if="canViewQuestions" class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linecap="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Questions</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.questions_count }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <router-link to="/questions" class="text-sm text-indigo-600 hover:text-indigo-900">View all</router-link>
        </div>
      </div>
      <!-- Similar blocks for tests, invitations, etc. -->
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../utils/axios';

const auth = useAuthStore();
const stats = ref({});
const canViewQuestions = computed(() => ['admin', 'author', 'recruiter'].includes(auth.role));

onMounted(async () => {
  const res = await api.get('/dashboard/stats'); // you need to create this endpoint
  stats.value = res.data;
});
</script>