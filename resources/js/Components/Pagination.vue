<template>
  <div v-if="hasPagination" class="mt-4 flex items-center justify-between">
    <button
      class="rounded border px-3 py-1 text-sm disabled:opacity-50"
      :disabled="!pagination.prev_page_url"
      @click="$emit('page-change', pagination.current_page - 1)"
    >
      Previous
    </button>
    <span class="text-sm text-gray-600">
      Page {{ pagination.current_page }} of {{ pagination.last_page }}
    </span>
    <button
      class="rounded border px-3 py-1 text-sm disabled:opacity-50"
      :disabled="!pagination.next_page_url"
      @click="$emit('page-change', pagination.current_page + 1)"
    >
      Next
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  pagination: {
    type: Object,
    default: () => ({}),
  },
});

defineEmits(['page-change']);

const hasPagination = computed(() => {
  return Number(props.pagination?.last_page || 0) > 1;
});
</script>
