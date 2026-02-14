<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Candidate Report</h1>
        <p class="text-sm text-slate-500">{{ report?.test || 'Assessment Report' }}</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="!!downloadLoading"
          @click="download('pdf')"
        >
          {{ downloadLoading === 'pdf' ? 'Downloading...' : 'Download PDF' }}
        </button>
        <button
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="!!downloadLoading"
          @click="download('csv')"
        >
          {{ downloadLoading === 'csv' ? 'Downloading...' : 'Download CSV' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-slate-600 shadow-sm">
      Loading report...
    </div>

    <template v-else-if="report">
      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-slate-500">Candidate</p>
          <p class="mt-1 text-sm font-semibold text-slate-900">{{ report.candidate }}</p>
          <p class="text-xs text-slate-500">{{ report.candidate_email }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-slate-500">Total Score</p>
          <p class="mt-1 text-sm font-semibold text-slate-900">{{ report.score }} / {{ report.max_score }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-slate-500">Percentage</p>
          <p class="mt-1 text-sm font-semibold text-slate-900">{{ report.percentage }}%</p>
        </div>
      </div>

      <div class="space-y-4">
        <div v-for="section in report.sections" :key="section.title" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-slate-900">{{ section.title }}</h2>
            <p class="text-sm text-slate-600">Section Score: {{ section.score }} / {{ section.max_score }}</p>
          </div>
          <div class="space-y-3">
            <div v-for="question in section.questions" :key="question.id" class="rounded-lg border border-slate-200 p-3">
              <p class="text-sm font-medium text-slate-800">{{ question.description }}</p>
              <p class="mt-1 text-xs text-slate-500">Type: {{ question.type }}</p>
              <div class="mt-2 rounded-md bg-slate-50 p-2 text-sm text-slate-700">
                <template v-if="isCodeAnswer(question.candidate_answer)">
                  <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Language: {{ question.candidate_answer.language || 'N/A' }}
                  </p>
                  <pre class="mt-1 overflow-x-auto whitespace-pre-wrap font-mono text-xs">{{ question.candidate_answer.code }}</pre>
                </template>
                <template v-else-if="Array.isArray(question.candidate_answer)">
                  {{ question.candidate_answer.join(', ') || 'No answer' }}
                </template>
                <template v-else>
                  {{ question.candidate_answer || 'No answer' }}
                </template>
              </div>
              <p class="mt-2 text-sm text-slate-700">
                Marks: {{ question.marks_awarded }} / {{ question.max_marks }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const route = useRoute();
const alert = useAlertStore();
const id = route.params.id;
const loading = ref(true);
const report = ref(null);
const downloadLoading = ref(null);

onMounted(async () => {
  try {
    const response = await api.get(`/reports/attempt/${id}`);
    report.value = response.data;
  } finally {
    loading.value = false;
  }
});

function isCodeAnswer(answer) {
  return answer && typeof answer === 'object' && !Array.isArray(answer) && 'code' in answer;
}

async function download(format) {
  downloadLoading.value = format;
  try {
    const response = await api.get(`/reports/attempt/${id}/${format}`, {
      responseType: 'blob',
    });

    const contentType = (response.headers['content-type'] || '').toLowerCase();
    if (contentType.includes('application/json') || contentType.includes('text/html')) {
      const text = await response.data.text();
      let parsedMessage = '';

      try {
        const parsed = JSON.parse(text);
        parsedMessage = parsed?.message || '';
      } catch (parseError) {
        parsedMessage = contentType.includes('text/html')
          ? 'Download failed because your session is not authorized for this report.'
          : '';
      }

      throw new Error(parsedMessage || `Unable to download ${format.toUpperCase()}`);
    }

    const contentDisposition = response.headers['content-disposition'] || '';
    const filename = parseFilename(contentDisposition) || `report-${id}.${format}`;
    const blob = new Blob([response.data], { type: response.headers['content-type'] || 'application/octet-stream' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    let message = error.message || `Unable to download ${format.toUpperCase()}`;

    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text();
        const parsed = JSON.parse(text);
        if (parsed?.message) {
          message = parsed.message;
        }
      } catch (parseError) {
        // keep fallback message
      }
    } else if (error.response?.data?.message) {
      message = error.response.data.message;
    }

    alert.error(message);
  } finally {
    downloadLoading.value = null;
  }
}

function parseFilename(contentDisposition) {
  const match = /filename\*?=(?:UTF-8'')?["']?([^;"']+)/i.exec(contentDisposition);
  if (!match?.[1]) return null;
  return decodeURIComponent(match[1].replace(/["']/g, ''));
}
</script>
