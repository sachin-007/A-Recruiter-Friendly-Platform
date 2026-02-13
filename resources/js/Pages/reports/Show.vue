<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Candidate Report</h1>
      <div class="space-x-2">
        <a :href="`/api/v1/reports/attempt/${id}/pdf`" class="rounded bg-gray-900 px-3 py-2 text-sm text-white">Download PDF</a>
        <a :href="`/api/v1/reports/attempt/${id}/csv`" class="rounded border px-3 py-2 text-sm">Download CSV</a>
      </div>
    </div>

    <div v-if="loading" class="rounded bg-white p-6 shadow">Loading report...</div>

    <div v-else-if="report" class="grid gap-4 md:grid-cols-3">
      <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Candidate</p>
        <p class="font-medium">{{ report.candidate }}</p>
      </div>
      <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Total Score</p>
        <p class="font-medium">{{ report.score }} / {{ report.max_score }}</p>
      </div>
      <div class="rounded bg-white p-4 shadow">
        <p class="text-sm text-gray-500">Percentage</p>
        <p class="font-medium">{{ report.percentage }}%</p>
      </div>
    </div>

    <div v-if="report?.sections?.length" class="space-y-4">
      <div v-for="section in report.sections" :key="section.title" class="rounded bg-white p-4 shadow">
        <h2 class="mb-3 text-lg font-semibold">{{ section.title }}</h2>
        <div class="space-y-2">
          <div v-for="(question, idx) in section.questions" :key="idx" class="rounded border p-3">
            <p class="text-sm font-medium">{{ question.description }}</p>
            <p class="text-xs text-gray-500">Type: {{ question.type }}</p>
            <p class="text-sm">Marks: {{ question.marks_awarded }} / {{ question.max_marks }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../utils/axios';

const route = useRoute();
const id = route.params.id;
const loading = ref(true);
const report = ref(null);

onMounted(async () => {
  try {
    const response = await api.get(`/reports/attempt/${id}`);
    report.value = response.data;
  } finally {
    loading.value = false;
  }
});
</script>
