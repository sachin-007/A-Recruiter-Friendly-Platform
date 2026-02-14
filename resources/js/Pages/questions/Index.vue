<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Question Bank</h1>
        <p class="text-sm text-slate-500">Create, preview, and reuse questions across tests.</p>
      </div>
      <div class="flex items-center gap-2">
        <router-link
          v-if="canCreate"
          to="/questions/import"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Import CSV
        </router-link>
        <router-link
          v-if="canCreate"
          to="/questions/create"
          class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
          New Question
        </router-link>
      </div>
    </div>

    <div class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
      <select v-model="filters.type" class="field-input">
        <option value="">All Types</option>
        <option value="mcq">MCQ</option>
        <option value="free_text">Free Text</option>
        <option value="coding">Coding</option>
        <option value="sql">SQL</option>
      </select>
      <select v-model="filters.difficulty" class="field-input">
        <option value="">All Difficulties</option>
        <option value="easy">Easy</option>
        <option value="medium">Medium</option>
        <option value="hard">Hard</option>
      </select>
      <select v-model="filters.status" class="field-input">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="active">Active</option>
        <option value="archived">Archived</option>
      </select>
      <select v-model="filters.tag" class="field-input">
        <option value="">All Tags</option>
        <option v-for="tag in tags" :key="tag.id" :value="tag.id">{{ tag.name }}</option>
      </select>
      <select v-model="filters.created_by" class="field-input">
        <option value="">Created by: Anyone</option>
        <option value="me">Created by: Me</option>
      </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Question</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Difficulty</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tags</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created By</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr v-for="question in questions.data" :key="question.id">
            <td class="px-4 py-3 text-sm text-slate-700">
              <p class="font-medium text-slate-900">{{ question.title || truncate(question.description, 60) }}</p>
              <p class="text-xs text-slate-500">{{ truncate(question.description, 90) }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">{{ question.type }}</td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-2 py-1 text-xs font-medium', difficultyClass(question.difficulty)]">
                {{ question.difficulty }}
              </span>
            </td>
            <td class="px-4 py-3 text-xs text-slate-600">
              <span
                v-for="tag in question.tags"
                :key="tag.id"
                class="mr-1 inline-block rounded-full bg-slate-200 px-2 py-1"
              >
                {{ tag.name }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ question.created_by || '-' }}</td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusClass(question.status)]">
                {{ question.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right text-sm">
              <router-link :to="`/questions/${question.id}/edit`" class="mr-3 font-medium text-indigo-600 hover:text-indigo-800">Edit</router-link>
              <button @click="deleteQuestion(question.id)" class="font-medium text-rose-600 hover:text-rose-800">Delete</button>
            </td>
          </tr>
          <tr v-if="!questions.data.length">
            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">No questions found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :pagination="questions" @page-change="fetchQuestions" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import Pagination from '../../Components/Pagination.vue';
import { useAuthStore } from '../../stores/auth';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const auth = useAuthStore();
const alert = useAlertStore();
const questions = ref({ data: [] });
const tags = ref([]);

const filters = reactive({
  type: '',
  difficulty: '',
  status: '',
  tag: '',
  created_by: '',
});

const canCreate = computed(() => ['admin', 'author'].includes(auth.role));

onMounted(async () => {
  await Promise.all([fetchQuestions(), fetchTags()]);
});

watch(
  filters,
  () => {
    fetchQuestions();
  },
  { deep: true }
);

async function fetchQuestions(page = 1) {
  const response = await api.get('/questions', {
    params: {
      ...filters,
      page,
    },
  });
  questions.value = response.data;
}

async function fetchTags() {
  try {
    const response = await api.get('/tags', { params: { per_page: 200 } });
    tags.value = response.data.data || [];
  } catch (error) {
    tags.value = [];
  }
}

async function deleteQuestion(id) {
  if (!window.confirm('Delete this question?')) return;
  try {
    await api.delete(`/questions/${id}`);
    alert.success('Question deleted');
    fetchQuestions();
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to delete question');
  }
}

function truncate(html = '', length = 80) {
  const text = html.replace(/<[^>]*>/g, '');
  return text.length > length ? `${text.slice(0, length)}...` : text;
}

function difficultyClass(difficulty) {
  return {
    easy: 'bg-emerald-100 text-emerald-700',
    medium: 'bg-amber-100 text-amber-700',
    hard: 'bg-rose-100 text-rose-700',
  }[difficulty] || 'bg-slate-100 text-slate-700';
}

function statusClass(status) {
  return {
    draft: 'bg-slate-100 text-slate-700',
    active: 'bg-emerald-100 text-emerald-700',
    archived: 'bg-rose-100 text-rose-700',
  }[status] || 'bg-slate-100 text-slate-700';
}
</script>
