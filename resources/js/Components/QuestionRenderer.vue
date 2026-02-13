<template>
  <div>
    <!-- MCQ -->
    <div v-if="question.type === 'mcq'" class="space-y-2">
      <div v-for="option in question.options" :key="option.id" class="flex items-center">
        <input
          :type="multipleCorrect ? 'checkbox' : 'radio'"
          :name="`q_${question.id}`"
          :value="option.id"
          v-model="selected"
          class="mr-2"
        />
        <span v-html="option.option_text"></span>
      </div>
    </div>

    <!-- Free Text -->
    <div v-else-if="question.type === 'free_text'">
      <textarea
        v-model="selected"
        rows="5"
        class="w-full border rounded p-2"
        :placeholder="`Word limit: ${question.word_limit || 'none'}`"
      ></textarea>
      <div v-if="question.word_limit" class="text-sm text-gray-500 mt-1">
        {{ wordCount }} / {{ question.word_limit }} words
      </div>
    </div>

    <!-- Coding / SQL -->
    <div v-else>
      <textarea
        v-model="selected"
        rows="8"
        class="w-full border rounded p-2 font-mono"
        placeholder="Write your answer here..."
      ></textarea>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  question: { type: Object, required: true },
  modelValue: { type: [String, Array, Object], default: null },
});

const emit = defineEmits(['update:modelValue']);

const selected = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const multipleCorrect = computed(() => {
  if (props.question.type === 'mcq') {
    const correctCount = props.question.options.filter(o => o.is_correct).length;
    return correctCount > 1;
  }
  return false;
});

const wordCount = computed(() => {
  if (typeof selected.value === 'string') {
    return selected.value.trim().split(/\s+/).filter(Boolean).length;
  }
  return 0;
});
</script>