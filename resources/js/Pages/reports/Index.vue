<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Reports</h1>
        <p class="text-sm text-slate-500">
          Overall and section-wise candidate performance{{ isSuperAdmin ? ' across organizations.' : '.' }}
        </p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Candidate</th>
            <th v-if="isSuperAdmin" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Organization</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Test</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Shared By</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Score</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Completed</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr v-for="attempt in reports.data" :key="attempt.id">
            <td class="px-4 py-3 text-sm text-slate-700">
              <div class="font-medium text-slate-900">{{ attempt.candidate }}</div>
              <div class="text-xs text-slate-500">{{ attempt.candidate_email }}</div>
            </td>
            <td v-if="isSuperAdmin" class="px-4 py-3 text-sm text-slate-700">
              {{ attempt.organization_name || '-' }}
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              <div class="font-medium text-slate-900">{{ attempt.test_title || '-' }}</div>
              <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ attempt.invitation_status || 'unknown' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              <div class="font-medium text-slate-900">{{ attempt.shared_by_name || 'N/A' }}</div>
              <div class="text-xs text-slate-500">{{ attempt.shared_by_email || '-' }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              {{ attempt.score_total }} ({{ attempt.score_percent }}%)
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">
              {{ formatDate(attempt.completed_at) }}
            </td>
            <td class="px-4 py-3 text-right">
              <router-link :to="`/reports/attempt/${attempt.id}`" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                View Report
              </router-link>
            </td>
          </tr>
          <tr v-if="!reports.data.length">
            <td :colspan="isSuperAdmin ? 7 : 6" class="px-4 py-6 text-center text-sm text-slate-500">
              No completed assessments yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :pagination="reports" @page-change="fetchReports" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Pagination from '../../Components/Pagination.vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../utils/axios';

const auth = useAuthStore();
const reports = ref({ data: [] });
const isSuperAdmin = computed(() => auth.role === 'super_admin');

onMounted(() => {
  fetchReports();
});

async function fetchReports(page = 1) {
  const response = await api.get('/reports', { params: { page } });
  reports.value = response.data;
}

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleString();
}
</script>
