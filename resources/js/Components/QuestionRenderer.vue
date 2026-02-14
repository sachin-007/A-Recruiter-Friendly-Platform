<template>
  <div>
    <div v-if="question.type === 'mcq'" class="space-y-2">
      <label
        v-for="option in question.options || []"
        :key="option.id"
        class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50"
      >
        <input
          v-if="multipleCorrect"
          type="checkbox"
          :checked="selectedArray.includes(String(option.id))"
          class="mt-0.5"
          @change="toggleOption(option.id, $event.target.checked)"
        />
        <input
          v-else
          type="radio"
          :name="`q_${question.id}`"
          :checked="selectedSingle === String(option.id)"
          class="mt-0.5"
          @change="setSingle(option.id)"
        />
        <span class="text-sm text-slate-700">{{ option.option_text }}</span>
      </label>
    </div>

    <div v-else-if="question.type === 'free_text'">
      <textarea
        v-model="textAnswer"
        rows="6"
        class="w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
        :placeholder="`Word limit: ${question.word_limit || 'none'}`"
      ></textarea>
      <div v-if="question.word_limit" class="mt-1 text-xs text-slate-500">
        {{ wordCount }} / {{ question.word_limit }} words
      </div>
    </div>

    <div v-else class="space-y-2">
      <div class="flex items-center gap-2">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Language</label>
        <select v-model="codeLanguage" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
          <option v-for="language in languageOptions" :key="language" :value="language">
            {{ language }}
          </option>
        </select>
      </div>
      <textarea
        v-model="codeBody"
        rows="10"
        class="w-full rounded-lg border border-slate-300 bg-slate-950 p-3 font-mono text-sm text-slate-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
        :placeholder="codePlaceholder"
        spellcheck="false"
      ></textarea>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  question: { type: Object, required: true },
  modelValue: { type: [String, Array, Object, Number], default: null },
});

const emit = defineEmits(['update:modelValue']);

const multipleCorrect = computed(() => {
  if (props.question.type !== 'mcq') return false;
  const correctCount = (props.question.options || []).filter((option) => option.is_correct).length;
  return correctCount > 1;
});

const selectedArray = computed(() => {
  if (!Array.isArray(props.modelValue)) return [];
  return props.modelValue.map((value) => String(value));
});

const selectedSingle = computed(() => {
  if (Array.isArray(props.modelValue)) return '';
  return props.modelValue !== null && props.modelValue !== undefined ? String(props.modelValue) : '';
});

function toggleOption(optionId, checked) {
  const id = String(optionId);
  const next = new Set(selectedArray.value);
  if (checked) {
    next.add(id);
  } else {
    next.delete(id);
  }
  emit('update:modelValue', Array.from(next));
}

function setSingle(optionId) {
  emit('update:modelValue', String(optionId));
}

const textAnswer = computed({
  get() {
    return typeof props.modelValue === 'string' ? props.modelValue : '';
  },
  set(value) {
    emit('update:modelValue', value);
  },
});

const wordCount = computed(() => {
  return textAnswer.value.trim().split(/\s+/).filter(Boolean).length;
});

const languageOptions = computed(() => {
  if (props.question.type === 'sql') return ['sql'];
  return ['python', 'javascript', 'java', 'cpp', 'csharp', 'go', 'php', 'ruby', 'typescript', 'other'];
});

const codePlaceholder = computed(() => {
  return props.question.type === 'sql'
    ? 'Write your SQL solution here...'
    : 'Write your code solution here...';
});

const codeLanguage = computed({
  get() {
    return normalizeCodeAnswer(props.modelValue).language;
  },
  set(language) {
    const current = normalizeCodeAnswer(props.modelValue);
    emit('update:modelValue', { ...current, language });
  },
});

const codeBody = computed({
  get() {
    return normalizeCodeAnswer(props.modelValue).code;
  },
  set(code) {
    const current = normalizeCodeAnswer(props.modelValue);
    emit('update:modelValue', { ...current, code });
  },
});

function normalizeCodeAnswer(value) {
  const fallback = props.question.type === 'sql' ? 'sql' : 'python';

  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return {
      language: value.language || fallback,
      code: value.code || '',
    };
  }

  if (typeof value === 'string') {
    return {
      language: fallback,
      code: value,
    };
  }

  return {
    language: fallback,
    code: '',
  };
}
</script>
