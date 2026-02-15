<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">{{ test?.title }}</h1>
        <p class="text-sm text-gray-600">{{ test?.description }}</p>
        <p class="text-xs text-gray-500 mt-1">
          Duration: {{ test?.duration_minutes }} min | Status: {{ test?.status }}
        </p>
      </div>
      <router-link
        v-if="canEdit"
        :to="`/tests/${id}/edit`"
        class="rounded border px-3 py-2 text-sm"
      >
        Edit
      </router-link>
    </div>

    <div v-if="canEdit" class="rounded bg-white p-6 shadow">
      <h2 class="mb-2 text-lg font-semibold">Section Builder</h2>
      <p class="mb-4 text-sm text-gray-600">Add section title, then select and attach questions below.</p>
      <form class="grid gap-3 md:grid-cols-3" @submit.prevent="createSection">
        <input
          v-model="newSection.title"
          class="rounded border px-3 py-2"
          placeholder="Section title"
          required
        />
        <input
          v-model="newSection.description"
          class="rounded border px-3 py-2"
          placeholder="Description (optional)"
        />
        <button
          class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-60"
          :disabled="sectionSaving"
        >
          {{ sectionSaving ? 'Adding...' : 'Add Section' }}
        </button>
      </form>
    </div>

    <div class="rounded bg-white p-6 shadow">
      <h2 class="mb-3 text-lg font-semibold">Sections</h2>

      <div v-if="!test?.sections?.length" class="text-sm text-gray-600">
        No sections added yet.
      </div>

      <div v-else class="space-y-4">
        <div v-for="section in test.sections" :key="section.id" class="rounded border p-4">
          <div class="flex items-start justify-between">
            <div>
              <p class="font-medium">{{ section.title }}</p>
              <p class="text-sm text-gray-600">{{ section.description || '-' }}</p>
            </div>
            <button
              v-if="canEdit"
              @click="removeSection(section.id)"
              class="text-sm text-red-600 hover:text-red-800"
            >
              Delete Section
            </button>
          </div>

          <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b text-left text-gray-500">
                  <th class="py-2">Question</th>
                  <th class="py-2">Type</th>
                  <th class="py-2">Marks</th>
                  <th class="py-2">Optional</th>
                  <th class="py-2 text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="q in section.questions || []" :key="q.id" class="border-b last:border-b-0">
                  <td class="py-2 pr-3">{{ q.title || truncate(q.description, 70) }}</td>
                  <td class="py-2 pr-3">{{ q.type }}</td>
                  <td class="py-2 pr-3">{{ q.pivot?.marks ?? q.marks_default }}</td>
                  <td class="py-2 pr-3">{{ q.pivot?.is_optional ? 'Yes' : 'No' }}</td>
                  <td class="py-2 text-right">
                    <button
                      v-if="canEdit"
                      @click="detachQuestion(section.id, q.id)"
                      class="text-red-600 hover:text-red-800"
                    >
                      Remove
                    </button>
                  </td>
                </tr>
                <tr v-if="!(section.questions || []).length">
                  <td colspan="5" class="py-2 text-gray-500">No questions attached yet.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <form
            v-if="canEdit"
            class="mt-4 grid gap-3 md:grid-cols-5"
            @submit.prevent="attachQuestion(section.id)"
          >
            <select
              v-model="attachForms[section.id].question_id"
              class="rounded border px-3 py-2 md:col-span-2"
              required
            >
              <option value="">Select question</option>
              <option
                v-for="question in attachableQuestions(section)"
                :key="question.id"
                :value="question.id"
              >
                {{ question.title || truncate(question.description, 40) }} ({{ question.type }})
              </option>
            </select>
            <input
              v-model.number="attachForms[section.id].marks"
              type="number"
              min="1"
              class="rounded border px-3 py-2"
              placeholder="Marks"
              required
            />
            <label class="flex items-center gap-2 rounded border px-3 py-2">
              <input v-model="attachForms[section.id].is_optional" type="checkbox" />
              Optional
            </label>
            <button
              class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-60"
              :disabled="attachLoading[section.id]"
            >
              {{ attachLoading[section.id] ? 'Attaching...' : 'Attach Question' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const route = useRoute();
const auth = useAuthStore();
const alert = useAlertStore();

const id = route.params.id;
const test = ref(null);
const questionBank = ref([]);
const sectionSaving = ref(false);
const newSection = reactive({
  title: '',
  description: '',
});
const attachForms = reactive({});
const attachLoading = reactive({});

const canEdit = computed(() => ['super_admin', 'admin', 'author', 'recruiter'].includes(auth.role));

onMounted(async () => {
  await Promise.all([fetchTest(), fetchQuestionBank()]);
});

async function fetchTest() {
  const response = await api.get(`/tests/${id}`);
  test.value = response.data.data ?? response.data;

  for (const section of test.value.sections || []) {
    if (!attachForms[section.id]) {
      attachForms[section.id] = {
        question_id: '',
        marks: 1,
        is_optional: false,
      };
    }
  }
}

async function fetchQuestionBank() {
  const response = await api.get('/questions', { params: { per_page: 200 } });
  questionBank.value = response.data.data || [];
}

function truncate(text, length) {
  if (!text) return '';
  const clean = text.replace(/<[^>]*>/g, '');
  return clean.length > length ? `${clean.slice(0, length)}...` : clean;
}

async function createSection() {
  sectionSaving.value = true;
  try {
    await api.post(`/tests/${id}/sections`, {
      title: newSection.title,
      description: newSection.description || null,
    });
    newSection.title = '';
    newSection.description = '';
    await fetchTest();
    alert.success('Section added');
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to add section');
  } finally {
    sectionSaving.value = false;
  }
}

async function removeSection(sectionId) {
  if (!confirm('Delete this section?')) return;
  try {
    await api.delete(`/tests/${id}/sections/${sectionId}`);
    await fetchTest();
    alert.success('Section deleted');
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to delete section');
  }
}

function attachableQuestions(section) {
  const attached = new Set((section.questions || []).map((q) => q.id));
  return questionBank.value.filter((q) => !attached.has(q.id));
}

async function attachQuestion(sectionId) {
  const form = attachForms[sectionId];
  if (!form?.question_id) return;

  attachLoading[sectionId] = true;
  try {
    await api.post(`/sections/${sectionId}/questions`, {
      question_id: form.question_id,
      marks: form.marks || 1,
      is_optional: !!form.is_optional,
    });

    form.question_id = '';
    form.marks = 1;
    form.is_optional = false;

    await fetchTest();
    alert.success('Question attached');
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to attach question');
  } finally {
    attachLoading[sectionId] = false;
  }
}

async function detachQuestion(sectionId, questionId) {
  if (!confirm('Remove this question from section?')) return;
  try {
    await api.delete(`/sections/${sectionId}/questions/${questionId}`);
    await fetchTest();
    alert.success('Question removed');
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to remove question');
  }
}
</script>
