<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Invitations</h1>
      <router-link to="/invitations/send" class="rounded bg-indigo-600 px-4 py-2 text-white">Send Invitation</router-link>
    </div>

    <div class="overflow-hidden rounded bg-white shadow">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Candidate</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Test</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Sent</th>
            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="invitation in invitations.data" :key="invitation.id">
            <td class="px-4 py-3">{{ invitation.candidate_name || invitation.candidate_email }}</td>
            <td class="px-4 py-3">{{ invitation.test?.title || '-' }}</td>
            <td class="px-4 py-3">{{ invitation.status }}</td>
            <td class="px-4 py-3">{{ formatDate(invitation.sent_at) }}</td>
            <td class="px-4 py-3 text-right">
              <router-link
                v-if="invitation.attempt?.id && invitation.status === 'completed'"
                :to="`/reports/attempt/${invitation.attempt.id}`"
                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
              >
                View Report
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../utils/axios';

const invitations = ref({ data: [] });

onMounted(async () => {
  const response = await api.get('/invitations');
  invitations.value = response.data;
});

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleString();
}
</script>
