<template>
  <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,380px)]">
    <div class="space-y-4">
      <h1 class="text-2xl font-semibold text-slate-900">{{ isEdit ? 'Edit Question' : 'Create Question' }}</h1>

      <form class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
        <div v-if="errorMessages.length" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
          <p class="mb-1 font-semibold">Please fix the following:</p>
          <ul class="space-y-1">
            <li v-for="(message, idx) in errorMessages" :key="idx">{{ message }}</li>
          </ul>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <div>
            <label class="field-label">Type</label>
            <select v-model="form.type" class="field-input">
              <option value="mcq">MCQ</option>
              <option value="free_text">Free Text</option>
              <option value="coding">Coding</option>
              <option value="sql">SQL</option>
            </select>
          </div>
          <div>
            <label class="field-label">Difficulty</label>
            <select v-model="form.difficulty" class="field-input">
              <option value="easy">Easy</option>
              <option value="medium">Medium</option>
              <option value="hard">Hard</option>
            </select>
          </div>
          <div>
            <label class="field-label">Status</label>
            <select v-model="form.status" class="field-input">
              <option value="draft">Draft</option>
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>

        <div>
          <label class="field-label">Title</label>
          <input v-model="form.title" class="field-input" placeholder="Question title" />
        </div>

        <div>
          <label class="field-label">Description</label>
          <textarea v-model="form.description" class="field-input min-h-[140px]" rows="6" placeholder="Question statement"></textarea>
        </div>

        <div v-if="['coding', 'sql'].includes(form.type)" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Code Block Helper</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="language in supportedLanguages"
              :key="language"
              type="button"
              class="btn-secondary !px-3 !py-1.5 !text-xs"
              @click="insertCodeTemplate(language)"
            >
              {{ language }}
            </button>
          </div>
        </div>

        <div>
          <label class="field-label">Explanation (optional)</label>
          <textarea v-model="form.explanation" class="field-input min-h-[96px]" rows="4" placeholder="Reference explanation"></textarea>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="field-label">Default Marks</label>
            <input v-model.number="form.marks_default" type="number" min="1" class="field-input" />
          </div>
          <div v-if="form.type === 'free_text'">
            <label class="field-label">Word Limit</label>
            <input v-model.number="form.word_limit" type="number" min="1" class="field-input" />
          </div>
        </div>

        <div>
          <label class="field-label">Tags</label>
          <select v-model="form.tags" multiple class="field-input min-h-[120px]">
            <option v-for="tag in availableTags" :key="tag.id" :value="Number(tag.id)">
              {{ tag.name }}
            </option>
          </select>
          <p class="mt-1 text-xs text-slate-500">Use Ctrl/Cmd + click to select multiple tags.</p>
          <p v-if="!availableTags.length" class="mt-1 text-xs text-amber-600">
            No tags available yet. Create one below.
          </p>
          <div class="mt-3 flex gap-2">
            <input
              v-model.trim="newTagName"
              class="field-input"
              placeholder="Create new tag (e.g. arrays, joins, recursion)"
            />
            <button
              type="button"
              class="btn-secondary whitespace-nowrap"
              :disabled="creatingTag || !newTagName"
              @click="createTag"
            >
              {{ creatingTag ? 'Adding...' : 'Add Tag' }}
            </button>
          </div>
        </div>

        <div v-if="form.type === 'mcq'" class="space-y-3 rounded-lg border border-slate-200 p-4">
          <div class="flex items-center justify-between">
            <h2 class="font-medium text-slate-900">Options</h2>
            <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="addOption">
              + Add option
            </button>
          </div>

          <div v-for="(option, index) in form.options" :key="index" class="grid gap-2 rounded-lg border border-slate-200 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto]">
            <input
              v-model="option.option_text"
              class="field-input"
              :placeholder="`Option ${index + 1}`"
            />
            <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700">
              <input v-model="option.is_correct" type="checkbox" />
              Correct
            </label>
            <button
              type="button"
              class="rounded-lg border border-rose-300 px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"
              @click="removeOption(index)"
              :disabled="form.options.length <= 2"
            >
              Remove
            </button>
          </div>

          <p class="text-xs text-slate-500">
            MCQ requires at least two options and at least one correct answer.
          </p>
        </div>

        <div class="flex justify-end gap-2">
          <router-link to="/questions" class="btn-secondary">Cancel</router-link>
          <button class="btn-primary" :disabled="loading">
            {{ loading ? 'Saving...' : 'Save Question' }}
          </button>
        </div>
      </form>
    </div>

    <aside class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Candidate Preview</h2>
      <p class="mb-4 text-xs text-slate-500">Preview question exactly how candidate sees it.</p>
      <div class="space-y-3 rounded-lg border border-slate-200 p-4">
        <p class="text-sm font-medium text-slate-800">{{ form.title || 'Untitled question' }}</p>
        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ form.description || 'Question description will appear here.' }}</p>
        <QuestionRenderer :question="previewQuestion" v-model="previewAnswer" />
      </div>
    </aside>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAlertStore } from '../../stores/alert';
import QuestionRenderer from '../../Components/QuestionRenderer.vue';
import api from '../../utils/axios';

const route = useRoute();
const router = useRouter();
const alert = useAlertStore();

const loading = ref(false);
const availableTags = ref([]);
const errorMessages = ref([]);
const previewAnswer = ref(null);
const newTagName = ref('');
const creatingTag = ref(false);

const isEdit = computed(() => Boolean(route.params.id));
const supportedLanguages = ['python', 'javascript', 'java', 'sql', 'cpp'];

const form = ref({
  type: 'mcq',
  title: '',
  description: '',
  difficulty: 'medium',
  explanation: '',
  word_limit: null,
  marks_default: 1,
  status: 'draft',
  options: defaultMcqOptions(),
  tags: [],
});

const previewQuestion = computed(() => {
  return {
    id: 'preview-question',
    type: form.value.type,
    description: form.value.description || '',
    word_limit: form.value.word_limit,
    options: (form.value.options || []).map((option, index) => ({
      ...option,
      id: option.id || `preview-option-${index}`,
    })),
  };
});

watch(
  () => form.value.type,
  (type) => {
    errorMessages.value = [];
    previewAnswer.value = null;

    if (type === 'mcq' && (!Array.isArray(form.value.options) || form.value.options.length < 2)) {
      form.value.options = defaultMcqOptions();
    }

    if (type !== 'free_text') {
      form.value.word_limit = null;
    }
  }
);

onMounted(async () => {
  await fetchTags();

  if (!isEdit.value) return;

  const response = await api.get(`/questions/${route.params.id}`);
  const data = response.data.data ?? response.data;
  form.value = {
    ...form.value,
    ...data,
    tags: data.tags?.map((tag) => Number(tag.id)) ?? [],
    options: data.options?.length ? data.options : defaultMcqOptions(),
  };
});

async function fetchTags() {
  try {
    const response = await api.get('/tags', { params: { per_page: 200 } });
    availableTags.value = response.data.data || [];
  } catch (error) {
    availableTags.value = [];
  }
}

function defaultMcqOptions() {
  return [
    { option_text: '', is_correct: false, order: 1 },
    { option_text: '', is_correct: false, order: 2 },
  ];
}

function addOption() {
  form.value.options.push({
    option_text: '',
    is_correct: false,
    order: form.value.options.length + 1,
  });
}

function removeOption(index) {
  if (form.value.options.length <= 2) return;
  form.value.options.splice(index, 1);
  form.value.options.forEach((option, idx) => {
    option.order = idx + 1;
  });
}

function insertCodeTemplate(language) {
  const snippet = `\n\`\`\`${language}\n// write your ${language} solution here\n\`\`\`\n`;
  form.value.description = `${form.value.description || ''}${snippet}`.trim();
}

function buildPayload() {
  const payload = {
    type: form.value.type,
    title: form.value.title || null,
    description: form.value.description,
    difficulty: form.value.difficulty,
    explanation: form.value.explanation || null,
    marks_default: form.value.marks_default || 1,
    status: form.value.status,
    tags: (form.value.tags || []).map((tagId) => Number(tagId)),
    word_limit: form.value.type === 'free_text' ? form.value.word_limit : null,
  };

  if (form.value.type === 'mcq') {
    payload.options = (form.value.options || []).map((option, index) => ({
      option_text: option.option_text,
      is_correct: !!option.is_correct,
      order: index + 1,
    }));
  }

  return payload;
}

async function createTag() {
  if (!newTagName.value) return;
  creatingTag.value = true;
  try {
    const response = await api.post('/tags', { name: newTagName.value });
    const created = response.data.data ?? response.data;
    const normalized = {
      id: Number(created.id),
      name: created.name,
    };

    availableTags.value = [...availableTags.value, normalized].sort((a, b) => a.name.localeCompare(b.name));

    if (!form.value.tags.includes(normalized.id)) {
      form.value.tags.push(normalized.id);
    }

    newTagName.value = '';
    alert.success('Tag created');
  } catch (error) {
    const message = error.response?.data?.errors?.name?.[0] || error.response?.data?.message || 'Unable to create tag';
    alert.error(message);
  } finally {
    creatingTag.value = false;
  }
}

async function submit() {
  loading.value = true;
  errorMessages.value = [];
  try {
    const payload = buildPayload();
    if (isEdit.value) {
      await api.put(`/questions/${route.params.id}`, payload);
      alert.success('Question updated');
    } else {
      await api.post('/questions', payload);
      alert.success('Question created');
    }
    router.push('/questions');
  } catch (error) {
    const errors = error.response?.data?.errors;
    if (errors) {
      errorMessages.value = Object.values(errors).flat();
    } else {
      errorMessages.value = [error.response?.data?.message || 'Unable to save question'];
    }
    alert.error('Please correct the form and try again');
  } finally {
    loading.value = false;
  }
}
</script>
