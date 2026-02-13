<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold">Question Bank</h1>
      <router-link v-if="canCreate" to="/questions/create" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
        New Question
      </router-link>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <select v-model="filters.type" class="border rounded px-3 py-2">
        <option value="">All Types</option>
        <option value="mcq">MCQ</option>
        <option value="free_text">Free Text</option>
        <option value="coding">Coding</option>
        <option value="sql">SQL</option>
      </select>
      <select v-model="filters.difficulty" class="border rounded px-3 py-2">
        <option value="">All Difficulties</option>
        <option value="easy">Easy</option>
        <option value="medium">Medium</option>
        <option value="hard">Hard</option>
      </select>
      <select v-model="filters.status" class="border rounded px-3 py-2">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="active">Active</option>
        <option value="archived">Archived</option>
      </select>
      <input v-model="filters.tag" placeholder="Filter by tag" class="border rounded px-3 py-2">
    </div>

    <!-- Table -->
    <div class="bg-white rounded shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Difficulty</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tags</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="question in questions.data" :key="question.id">
            <td class="px-6 py-4" v-html="truncate(question.description, 50)"></td>
            <td class="px-6 py-4">{{ question.type }}</td>
            <td class="px-6 py-4">
              <span :class="difficultyClass(question.difficulty)">{{ question.difficulty }}</span>
            </td>
            <td class="px-6 py-4">
              <span v-for="tag in question.tags" :key="tag.id" class="inline-block bg-gray-200 rounded-full px-2 py-1 text-xs mr-1">
                {{ tag.name }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span :class="statusClass(question.status)">{{ question.status }}</span>
            </td>
            <td class="px-6 py-4 text-right">
              <router-link :to="`/questions/${question.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</router-link>
              <button @click="deleteQuestion(question.id)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :pagination="questions" @page-change="fetchQuestions" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useAuthStore } from '../../../stores/auth';
import { useAlertStore } from '../../../stores/alert';
import api from '../../../utils/axios';
import Pagination from '../../../components/Pagination.vue';

const auth = useAuthStore();
const alert = useAlertStore();
const questions = ref({ data: [] });
const filters = reactive({
  type: '',
  difficulty: '',
  status: '',
  tag: '',
});
const canCreate = computed(() => ['admin', 'author'].includes(auth.role));

const fetchQuestions = async (page = 1) => {
  const params = { ...filters, page };
  const res = await api.get('/questions', { params });
  questions.value = res.data;
};

const deleteQuestion = async (id) => {
  if (confirm('Are you sure?')) {
    await api.delete(`/questions/${id}`);
    alert.success('Question deleted');
    fetchQuestions();
  }
};

const truncate = (html, length) => {
  const text = html.replace(/<[^>]*>/g, '');
  return text.length > length ? text.substring(0, length) + '...' : text;
};

const difficultyClass = (difficulty) => ({
  'easy': 'text-green-600 bg-green-100',
  'medium': 'text-yellow-600 bg-yellow-100',
  'hard': 'text-red-600 bg-red-100',
}[difficulty] + ' px-2 py-1 rounded text-xs');

const statusClass = (status) => ({
  'draft': 'text-gray-600 bg-gray-100',
  'active': 'text-green-600 bg-green-100',
  'archived': 'text-red-600 bg-red-100',
}[status] + ' px-2 py-1 rounded text-xs');

watch(filters, () => fetchQuestions(), { deep: true });
onMounted(fetchQuestions);
</script>