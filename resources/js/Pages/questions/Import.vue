<template>
  <div class="max-w-4xl space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">CSV Question Import</h1>
      <p class="mt-1 text-sm text-slate-500">
        Supports <strong>mcq</strong> and <strong>free_text</strong> rows. Imported records are saved as draft questions.
      </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <p class="text-sm font-medium text-slate-700">Expected Columns</p>
      <p class="mt-1 text-xs text-slate-500">
        Required: <code>type</code>, <code>description</code> |
        Optional: <code>title</code>, <code>difficulty</code>, <code>explanation</code>,
        <code>word_limit</code>, <code>marks_default</code>, <code>tags</code>,
        <code>options</code>, <code>correct_options</code>
      </p>
      <p class="mt-2 text-xs text-slate-500">
        MCQ format: options separated by <code>|</code>. Mark correct option using <code>*</code>
        prefix (example: <code>*Paris|London|Berlin</code>) or use
        <code>correct_options</code> as 1-based indexes (example: <code>1,3</code>).
      </p>
    </div>

    <form class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
      <label class="mb-2 block text-sm font-medium text-slate-700">CSV file</label>
      <input
        type="file"
        accept=".csv,.txt"
        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @change="onFileChange"
      />
      <div class="mt-4 flex justify-end">
        <button
          class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
          :disabled="loading || !file"
        >
          {{ loading ? 'Importing...' : 'Import Questions' }}
        </button>
      </div>
    </form>

    <div v-if="importResult" class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
          <p class="mt-1 text-sm font-semibold text-slate-800">{{ importResult.status }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-500">Total Rows</p>
          <p class="mt-1 text-sm font-semibold text-slate-800">{{ importResult.total_rows ?? 0 }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-500">Imported</p>
          <p class="mt-1 text-sm font-semibold text-slate-800">{{ importResult.processed_rows ?? 0 }}</p>
        </div>
      </div>

      <div v-if="importResult.error_log?.length" class="rounded-lg border border-rose-200 bg-rose-50 p-4">
        <p class="mb-2 text-sm font-semibold text-rose-700">Validation Errors</p>
        <ul class="space-y-2 text-sm text-rose-700">
          <li v-for="(entry, idx) in importResult.error_log" :key="idx">
            Line {{ entry.line }}: {{ entry.errors?.join(', ') }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onUnmounted, ref } from 'vue';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const alert = useAlertStore();
const file = ref(null);
const loading = ref(false);
const importResult = ref(null);
let pollHandle = null;

function onFileChange(event) {
  file.value = event.target.files?.[0] || null;
}

async function submit() {
  if (!file.value) return;
  loading.value = true;
  clearPoll();

  const payload = new FormData();
  payload.append('file', file.value);

  try {
    const response = await api.post('/imports/questions', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    importResult.value = response.data.data ?? response.data;
    alert.success('Import request submitted');
    startPolling();
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to import file');
  } finally {
    loading.value = false;
  }
}

function startPolling() {
  if (!importResult.value?.id) return;
  pollHandle = window.setInterval(async () => {
    const response = await api.get(`/imports/${importResult.value.id}`);
    importResult.value = response.data.data ?? response.data;
    if (['completed', 'failed'].includes(importResult.value.status)) {
      clearPoll();
    }
  }, 2000);
}

function clearPoll() {
  if (pollHandle) {
    window.clearInterval(pollHandle);
    pollHandle = null;
  }
}

onUnmounted(() => {
  clearPoll();
});
</script>
