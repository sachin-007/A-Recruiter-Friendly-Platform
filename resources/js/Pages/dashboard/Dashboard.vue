<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
      <p class="text-sm text-slate-500">Overview of your organization assessment activity.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Questions</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.questions_count ?? 0 }}</p>
        <router-link to="/questions" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">Manage</router-link>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Tests</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.tests_count ?? 0 }}</p>
        <router-link to="/tests" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">Manage</router-link>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Invitations</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.invitations_count ?? 0 }}</p>
        <router-link to="/invitations" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">Track</router-link>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Completed Attempts</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.completed_attempts_count ?? 0 }}</p>
        <router-link v-if="canViewReports" to="/reports" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">View Reports</router-link>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const auth = useAuthStore();
const alert = useAlertStore();
const stats = ref({});

const canViewReports = computed(() => ['admin', 'recruiter'].includes(auth.role));

onMounted(async () => {
  try {
    const response = await api.get('/dashboard/stats');
    stats.value = response.data;
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to load dashboard stats');
  }
});
</script>
