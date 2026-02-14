<template>
  <div v-if="loading" class="flex justify-center items-center h-screen">
    <div class="text-xl">Loading your test...</div>
  </div>
  <div v-else-if="linkError" class="max-w-xl mx-auto mt-10 p-6 bg-white rounded shadow">
    <h1 class="text-xl font-semibold mb-3">Unable to open assessment</h1>
    <p class="text-gray-700 mb-4">{{ linkError }}</p>
    <button
      v-if="canResend"
      @click="resendLink"
      class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
      :disabled="resendLoading"
    >
      {{ resendLoading ? 'Sending...' : 'Resend Link' }}
    </button>
  </div>
  <div v-else-if="!canStart" class="max-w-2xl mx-auto mt-10 p-6 bg-white rounded shadow">
    <h1 class="text-2xl font-bold mb-4">{{ test.title }}</h1>
    <div class="prose mb-6" v-html="test.instructions"></div>
    <button @click="startTest" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
      Start Test
    </button>
  </div>
  <div v-else class="max-w-4xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">{{ test.title }}</h1>
      <Timer :initial-seconds="timeRemaining" @tick="onTimerTick" @time-end="submitTest" />
    </div>
    <div class="grid grid-cols-4 gap-6">
      <div class="col-span-3">
        <div v-for="section in test.sections" :key="section.id" class="mb-8">
          <h2 class="text-xl font-semibold mb-2">{{ section.title }}</h2>
          <div :id="`question-${question.id}`" v-for="(question, index) in section.questions" :key="question.id" class="mb-6 p-4 border rounded">
            <div class="flex justify-between">
              <span class="font-medium">Question {{ index + 1 }}</span>
              <span class="text-sm bg-gray-200 px-2 py-1 rounded">{{ question.type }}</span>
            </div>
            <div class="my-3" v-html="question.description"></div>
            <QuestionRenderer
              :question="question"
              v-model="answers[question.id]"
            />
          </div>
        </div>
        <div class="flex justify-end">
          <button
            @click="submitTest"
            class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 disabled:opacity-60"
            :disabled="submitting"
          >
            {{ submitting ? 'Submitting...' : 'Submit Test' }}
          </button>
        </div>
      </div>
      <div class="col-span-1">
        <div class="sticky top-4 bg-gray-50 p-4 rounded">
          <h3 class="font-bold mb-2">Question Navigator</h3>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="(q, idx) in allQuestions"
              :key="q.id"
              @click="scrollToQuestion(q.id)"
              :class="[
                'w-8 h-8 rounded text-sm',
                answers[q.id] ? 'bg-green-500 text-white' : 'bg-gray-200',
                currentQuestionId === q.id ? 'ring-2 ring-blue-500' : ''
              ]"
            >
              {{ idx + 1 }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '../../utils/axios';
import Timer from '../../Components/Timer.vue';
import QuestionRenderer from '../../Components/QuestionRenderer.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const loading = ref(true);
const canStart = ref(false);
const test = ref(null);
const attempt = ref(null);
const answers = ref({});
const timeRemaining = ref(0);
const currentQuestionId = ref(null);
const linkError = ref('');
const canResend = ref(false);
const resendLoading = ref(false);
const submitting = ref(false);
const hasSubmitted = ref(false);
let saveInterval = null;
let saveDebounce = null;

const allQuestions = computed(() => {
  return test.value?.sections.flatMap(s => s.questions) || [];
});

onMounted(async () => {
  const token = route.params.token;
  const hasMatchingAttempt = !!auth.attempt && auth.invitation?.token === token;

  attempt.value = hasMatchingAttempt ? auth.attempt : null;

  if (!attempt.value) {
    try {
      const res = await api.post('/magic-link/verify', { token });
      auth.setToken(res.data.token);
      attempt.value = res.data.attempt;
      auth.attempt = attempt.value;
      auth.invitation = res.data.invitation;
    } catch (error) {
      linkError.value = error.response?.data?.message || 'Invalid or expired link.';
      canResend.value = !!error.response?.data?.resend_available;
      loading.value = false;
      return;
    }
  }

  try {
    const res = await api.get(`/attempts/${attempt.value.id}`);
    const attemptData = res.data.data ?? res.data;

    attempt.value = attemptData;
    auth.attempt = attemptData;
    test.value = attemptData.test;
    timeRemaining.value = attemptData.time_remaining ?? (test.value.duration_minutes * 60 || 3600);

    if (attemptData.answers) {
      answers.value = attemptData.answers.reduce((acc, a) => {
        acc[a.question_id] = a.answer_json;
        return acc;
      }, {});
    }

    if (attemptData.started_at && attemptData.status === 'in_progress') {
      canStart.value = true;
      saveInterval = setInterval(saveAnswers, 10000);
    }
  } catch (error) {
    linkError.value = error.response?.data?.message || 'Unable to load attempt data.';
    canResend.value = !!error.response?.data?.resend_available;
    loading.value = false;
    return;
  }

  loading.value = false;
});

async function startTest() {
  await api.post(`/attempts/${attempt.value.id}/start`);
  canStart.value = true;
  // Start timer
  saveInterval = setInterval(saveAnswers, 10000);
}

async function saveAnswers() {
  if (!attempt.value) return;
  const payload = {
    answers: Object.entries(answers.value).map(([questionId, answer]) => ({
      question_id: questionId,
      answer_json: answer,
    })),
    time_remaining: timeRemaining.value,
  };
  try {
    await api.put(`/attempts/${attempt.value.id}`, payload);
  } catch (error) {
    // Keep UI responsive; autosave retries on next interval/change.
  }
}

async function submitTest() {
  if (submitting.value) return;
  if (confirm('Are you sure you want to submit?')) {
    submitting.value = true;
    clearInterval(saveInterval);
    await saveAnswers();
    try {
      await api.post(`/attempts/${attempt.value.id}/submit`);
      hasSubmitted.value = true;
      auth.clearSession();
      await router.replace('/test-submitted');
    } finally {
      submitting.value = false;
    }
  }
}

function scrollToQuestion(questionId) {
  const el = document.getElementById(`question-${questionId}`);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
    currentQuestionId.value = questionId;
  }
}

function onTimerTick(secondsLeft) {
  timeRemaining.value = secondsLeft;
}

async function resendLink() {
  resendLoading.value = true;
  try {
    await api.post('/magic-link/resend', { token: route.params.token });
    linkError.value = 'A new magic link has been sent to your email.';
    canResend.value = false;
  } catch (error) {
    linkError.value = error.response?.data?.message || 'Unable to resend link.';
  } finally {
    resendLoading.value = false;
  }
}

onUnmounted(() => {
  clearInterval(saveInterval);
  clearTimeout(saveDebounce);
  if (canStart.value && !hasSubmitted.value) {
    saveAnswers(); // final save
  }
});

// Auto-save on answer change (debounced)
watch(answers, () => {
  if (!canStart.value) return;
  clearTimeout(saveDebounce);
  saveDebounce = setTimeout(() => {
    saveAnswers();
  }, 800);
}, { deep: true });
</script>
