<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Reports</h1>
        <p class="text-sm text-slate-500">Overall and section-wise candidate performance.</p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Candidate</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Test</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Score</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Completed</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr v-for="attempt in reports.data" :key="attempt.id">
            <td class="px-4 py-3 text-sm text-slate-700">
              <div>{{ attempt.candidate }}</div>
              <div class="text-xs text-slate-500">{{ attempt.candidate_email }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">{{ attempt.test_title }}</td>
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
            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
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
import { onMounted, ref } from 'vue';
import Pagination from '../../Components/Pagination.vue';
import api from '../../utils/axios';

const reports = ref({ data: [] });

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
