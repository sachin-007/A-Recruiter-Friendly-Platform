<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
      <p class="text-sm text-slate-500">
        {{ isSuperAdmin ? 'Platform-wide analytics across organizations.' : 'Overview of your organization assessment activity.' }}
      </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
      <article
        v-for="card in summaryCards"
        :key="card.key"
        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </article>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-900">Invitations by Status</h2>
          <p class="text-xs text-slate-500">Sent, opened, started and completed invitations.</p>
        </div>
        <div class="h-72">
          <Doughnut :data="invitationStatusData" :options="doughnutOptions" />
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-900">Questions by Type</h2>
          <p class="text-xs text-slate-500">Coverage across MCQ, coding, SQL and free-text.</p>
        </div>
        <div class="h-72">
          <Bar :data="questionTypeData" :options="barOptions" />
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-900">Questions by Difficulty</h2>
          <p class="text-xs text-slate-500">Distribution of easy, medium and hard questions.</p>
        </div>
        <div class="h-72">
          <Radar :data="difficultyData" :options="radarOptions" />
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-900">Completed Attempts Trend (14 days)</h2>
          <p class="text-xs text-slate-500">Daily completed assessments.</p>
        </div>
        <div class="h-72">
          <Line :data="attemptTrendData" :options="lineOptions" />
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2">
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-900">Top Tests Performance</h2>
          <p class="text-xs text-slate-500">Invitations vs completed attempts by test.</p>
        </div>
        <div class="h-80">
          <Bar :data="topTestsData" :options="stackedBarOptions" />
        </div>
      </section>

      <section
        v-if="isSuperAdmin && organizationOverviewData.labels.length"
        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2"
      >
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-900">Organization Overview</h2>
          <p class="text-xs text-slate-500">Users, tests and completed attempts by organization.</p>
        </div>
        <div class="h-80">
          <Bar :data="organizationOverviewData" :options="stackedBarOptions" />
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Bar, Doughnut, Line, Radar } from 'vue-chartjs';
import {
  ArcElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Filler,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  RadialLinearScale,
  Tooltip,
} from 'chart.js';
import { useAuthStore } from '../../stores/auth';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

ChartJS.register(
  ArcElement,
  BarElement,
  CategoryScale,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  RadialLinearScale,
  Tooltip,
  Filler
);

const auth = useAuthStore();
const alert = useAlertStore();

const stats = ref({
  questions_count: 0,
  tests_count: 0,
  invitations_count: 0,
  completed_attempts_count: 0,
  organizations_count: 0,
  users_count: 0,
  charts: {
    questions_by_type: { labels: [], data: [] },
    questions_by_difficulty: { labels: [], data: [] },
    invitations_by_status: { labels: [], data: [] },
    attempts_trend: { labels: [], data: [] },
    top_tests: { labels: [], invitations: [], completed_attempts: [] },
    organizations_overview: { labels: [], users: [], tests: [], completed_attempts: [] },
  },
});

const isSuperAdmin = computed(() => auth.role === 'super_admin');

const summaryCards = computed(() => {
  const cards = [
    { key: 'questions', label: 'Questions', value: stats.value.questions_count ?? 0 },
    { key: 'tests', label: 'Tests', value: stats.value.tests_count ?? 0 },
    { key: 'invitations', label: 'Invitations', value: stats.value.invitations_count ?? 0 },
    { key: 'attempts', label: 'Completed Attempts', value: stats.value.completed_attempts_count ?? 0 },
  ];

  if (isSuperAdmin.value) {
    cards.push(
      { key: 'organizations', label: 'Organizations', value: stats.value.organizations_count ?? 0 },
      { key: 'users', label: 'Users', value: stats.value.users_count ?? 0 }
    );
  }

  return cards;
});

const palette = {
  indigo: '#4f46e5',
  violet: '#7c3aed',
  teal: '#0d9488',
  amber: '#d97706',
  emerald: '#059669',
  rose: '#e11d48',
  slate: '#64748b',
  cyan: '#0891b2',
};

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { precision: 0 },
      grid: { color: '#e2e8f0' },
    },
    x: {
      grid: { display: false },
    },
  },
};

const stackedBarOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'top' },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { precision: 0 },
      grid: { color: '#e2e8f0' },
    },
    x: {
      grid: { display: false },
    },
  },
};

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { precision: 0 },
      grid: { color: '#e2e8f0' },
    },
    x: {
      grid: { display: false },
    },
  },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' },
  },
};

const radarOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    r: {
      beginAtZero: true,
      ticks: { precision: 0 },
      grid: { color: '#e2e8f0' },
    },
  },
};

const invitationStatusData = computed(() => ({
  labels: stats.value.charts.invitations_by_status.labels,
  datasets: [
    {
      label: 'Invitations',
      data: stats.value.charts.invitations_by_status.data,
      backgroundColor: [palette.indigo, palette.cyan, palette.amber, palette.emerald],
      borderWidth: 0,
    },
  ],
}));

const questionTypeData = computed(() => ({
  labels: stats.value.charts.questions_by_type.labels,
  datasets: [
    {
      label: 'Questions',
      data: stats.value.charts.questions_by_type.data,
      backgroundColor: [palette.indigo, palette.violet, palette.teal, palette.amber],
      borderRadius: 8,
      maxBarThickness: 48,
    },
  ],
}));

const difficultyData = computed(() => ({
  labels: stats.value.charts.questions_by_difficulty.labels,
  datasets: [
    {
      label: 'Questions',
      data: stats.value.charts.questions_by_difficulty.data,
      backgroundColor: 'rgba(79, 70, 229, 0.18)',
      borderColor: palette.indigo,
      pointBackgroundColor: palette.indigo,
      pointBorderColor: '#ffffff',
      pointRadius: 4,
    },
  ],
}));

const attemptTrendData = computed(() => ({
  labels: stats.value.charts.attempts_trend.labels,
  datasets: [
    {
      label: 'Completed Attempts',
      data: stats.value.charts.attempts_trend.data,
      borderColor: palette.teal,
      backgroundColor: 'rgba(13, 148, 136, 0.18)',
      fill: true,
      tension: 0.35,
    },
  ],
}));

const topTestsData = computed(() => ({
  labels: stats.value.charts.top_tests.labels,
  datasets: [
    {
      label: 'Invitations',
      data: stats.value.charts.top_tests.invitations,
      backgroundColor: palette.slate,
      borderRadius: 6,
    },
    {
      label: 'Completed Attempts',
      data: stats.value.charts.top_tests.completed_attempts,
      backgroundColor: palette.emerald,
      borderRadius: 6,
    },
  ],
}));

const organizationOverviewData = computed(() => ({
  labels: stats.value.charts.organizations_overview?.labels || [],
  datasets: [
    {
      label: 'Users',
      data: stats.value.charts.organizations_overview?.users || [],
      backgroundColor: palette.indigo,
      borderRadius: 6,
    },
    {
      label: 'Tests',
      data: stats.value.charts.organizations_overview?.tests || [],
      backgroundColor: palette.amber,
      borderRadius: 6,
    },
    {
      label: 'Completed Attempts',
      data: stats.value.charts.organizations_overview?.completed_attempts || [],
      backgroundColor: palette.emerald,
      borderRadius: 6,
    },
  ],
}));

onMounted(async () => {
  try {
    const response = await api.get('/dashboard/stats');
    stats.value = response.data;
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to load dashboard stats');
  }
});
</script>
