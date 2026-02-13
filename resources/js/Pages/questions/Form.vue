<template>
  <div class="max-w-4xl space-y-4">
    <h1 class="text-2xl font-semibold">{{ isEdit ? 'Edit Question' : 'Create Question' }}</h1>

    <form class="space-y-4 rounded bg-white p-6 shadow" @submit.prevent="submit">
      <div class="grid gap-4 md:grid-cols-2">
        <select v-model="form.type" class="rounded border px-3 py-2">
          <option value="mcq">MCQ</option>
          <option value="free_text">Free Text</option>
          <option value="coding">Coding</option>
          <option value="sql">SQL</option>
        </select>
        <select v-model="form.difficulty" class="rounded border px-3 py-2">
          <option value="easy">Easy</option>
          <option value="medium">Medium</option>
          <option value="hard">Hard</option>
        </select>
      </div>

      <input v-model="form.title" class="w-full rounded border px-3 py-2" placeholder="Title (optional)" />
      <textarea v-model="form.description" class="w-full rounded border px-3 py-2" rows="5" placeholder="Question description" />
      <textarea v-model="form.explanation" class="w-full rounded border px-3 py-2" rows="3" placeholder="Explanation (optional)" />

      <div class="grid gap-4 md:grid-cols-3">
        <input v-model.number="form.marks_default" type="number" min="1" class="rounded border px-3 py-2" placeholder="Marks" />
        <input v-model.number="form.word_limit" type="number" min="1" class="rounded border px-3 py-2" placeholder="Word limit" />
        <select v-model="form.status" class="rounded border px-3 py-2">
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="archived">Archived</option>
        </select>
      </div>

      <div v-if="form.type === 'mcq'" class="space-y-2">
        <h2 class="font-medium">Options</h2>
        <div v-for="(option, index) in form.options" :key="index" class="flex items-center gap-2">
          <input v-model="option.option_text" class="flex-1 rounded border px-3 py-2" placeholder="Option text" />
          <label class="flex items-center gap-1 text-sm">
            <input v-model="option.is_correct" type="checkbox" />
            Correct
          </label>
          <button type="button" class="text-sm text-red-600" @click="removeOption(index)">Remove</button>
        </div>
        <button type="button" class="text-sm text-indigo-600" @click="addOption">+ Add option</button>
      </div>

      <div class="flex justify-end">
        <button class="rounded bg-indigo-600 px-4 py-2 text-white" :disabled="loading">
          {{ loading ? 'Saving...' : 'Save Question' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../utils/axios';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const isEdit = computed(() => Boolean(route.params.id));

const form = ref({
  type: 'mcq',
  title: '',
  description: '',
  difficulty: 'medium',
  explanation: '',
  word_limit: null,
  marks_default: 1,
  status: 'draft',
  options: [{ option_text: '', is_correct: false, order: 1 }],
  tags: [],
});

onMounted(async () => {
  if (!isEdit.value) return;
  const response = await api.get(`/questions/${route.params.id}`);
  const data = response.data.data ?? response.data;
  form.value = {
    ...form.value,
    ...data,
    tags: data.tags?.map((tag) => tag.id) ?? [],
    options: data.options?.length ? data.options : [{ option_text: '', is_correct: false, order: 1 }],
  };
});

function addOption() {
  form.value.options.push({ option_text: '', is_correct: false, order: form.value.options.length + 1 });
}

function removeOption(index) {
  form.value.options.splice(index, 1);
}

async function submit() {
  loading.value = true;
  try {
    if (isEdit.value) {
      await api.put(`/questions/${route.params.id}`, form.value);
    } else {
      await api.post('/questions', form.value);
    }
    router.push('/questions');
  } finally {
    loading.value = false;
  }
}
</script>
