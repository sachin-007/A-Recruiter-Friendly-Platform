<template>
  <div class="max-w-5xl space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">Organization Settings</h1>
      <p class="text-sm text-slate-500">Update branding, notification settings, and review default role permissions.</p>
    </div>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm">
      Loading organization settings...
    </div>

    <form v-else class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="save">
      <div>
        <label class="field-label">Organization Name</label>
        <input v-model="form.name" class="field-input" type="text" />
      </div>

      <div class="grid gap-5 md:grid-cols-2">
        <div>
          <label class="field-label">Organization Logo</label>
          <input type="file" accept="image/*" class="field-input" @change="onLogoChange" />
          <p class="mt-1 text-xs text-slate-500">PNG/JPG/WebP up to 2MB.</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Current Logo Preview</p>
          <img
            v-if="logoPreview"
            :src="logoPreview"
            alt="Organization logo"
            class="max-h-28 rounded border border-slate-200 bg-white p-2"
          />
          <p v-else class="text-sm text-slate-500">No logo uploaded.</p>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 p-4">
        <h2 class="text-sm font-semibold text-slate-800">Notifications</h2>
        <div class="mt-3 space-y-2">
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.settings.notifications.email" type="checkbox" />
            Email notifications
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.settings.notifications.sms" type="checkbox" />
            SMS notifications
          </label>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-800">Default Role Permissions (Read-only MVP)</h2>
          <p class="text-xs text-slate-500">These are system defaults for MVP and cannot be changed from UI.</p>
        </div>
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Feature</th>
                <th class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</th>
                <th class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Recruiter</th>
                <th class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Author</th>
                <th class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Candidate</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in rolePermissionMatrix" :key="row.feature">
                <td class="px-3 py-2 text-slate-700">{{ row.feature }}</td>
                <td class="px-3 py-2 text-center">{{ row.admin ? 'Yes' : '-' }}</td>
                <td class="px-3 py-2 text-center">{{ row.recruiter ? 'Yes' : '-' }}</td>
                <td class="px-3 py-2 text-center">{{ row.author ? 'Yes' : '-' }}</td>
                <td class="px-3 py-2 text-center">{{ row.candidate ? 'Yes' : '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex justify-end">
        <button class="btn-primary" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const alert = useAlertStore();

const loading = ref(true);
const saving = ref(false);
const organization = ref(null);
const logoFile = ref(null);
const logoPreview = ref('');
let localPreviewUrl = null;

const form = ref({
  name: '',
  settings: {
    notifications: {
      email: true,
      sms: false,
    },
  },
});

const rolePermissionMatrix = computed(() => ([
  { feature: 'Dashboard', admin: true, recruiter: true, author: true, candidate: false },
  { feature: 'Question Bank (view)', admin: true, recruiter: true, author: true, candidate: false },
  { feature: 'Question Bank (create/update)', admin: true, recruiter: false, author: true, candidate: false },
  { feature: 'Tests (create/update sections)', admin: true, recruiter: true, author: true, candidate: false },
  { feature: 'Invitations', admin: true, recruiter: true, author: false, candidate: false },
  { feature: 'Reports + Export PDF/CSV', admin: true, recruiter: true, author: false, candidate: false },
  { feature: 'CSV Question Import', admin: true, recruiter: false, author: true, candidate: false },
  { feature: 'User Management', admin: true, recruiter: false, author: false, candidate: false },
  { feature: 'Organization Settings', admin: true, recruiter: false, author: false, candidate: false },
  { feature: 'Take Assigned Assessment Only', admin: false, recruiter: false, author: false, candidate: true },
]));

onMounted(fetchOrganization);

onUnmounted(() => {
  if (localPreviewUrl) {
    URL.revokeObjectURL(localPreviewUrl);
  }
});

async function fetchOrganization() {
  loading.value = true;
  try {
    const response = await api.get('/organization');
    organization.value = response.data.data ?? response.data;

    form.value.name = organization.value.name || '';
    form.value.settings = {
      notifications: {
        email: organization.value.settings?.notifications?.email ?? true,
        sms: organization.value.settings?.notifications?.sms ?? false,
      },
    };

    logoPreview.value = organization.value.logo || '';
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to load organization settings');
  } finally {
    loading.value = false;
  }
}

function onLogoChange(event) {
  const file = event.target.files?.[0] || null;
  logoFile.value = file;

  if (!file) return;

  if (file.size > 2 * 1024 * 1024) {
    alert.error('Logo must be 2MB or smaller.');
    logoFile.value = null;
    return;
  }

  if (localPreviewUrl) {
    URL.revokeObjectURL(localPreviewUrl);
  }

  localPreviewUrl = URL.createObjectURL(file);
  logoPreview.value = localPreviewUrl;
}

async function save() {
  saving.value = true;
  try {
    const payload = new FormData();
    payload.append('name', form.value.name || '');
    payload.append('settings[notifications][email]', form.value.settings.notifications.email ? '1' : '0');
    payload.append('settings[notifications][sms]', form.value.settings.notifications.sms ? '1' : '0');

    if (logoFile.value) {
      payload.append('logo', logoFile.value);
    }

    // Use POST + method override for reliable multipart file upload on PHP setups.
    payload.append('_method', 'PUT');

    const response = await api.post('/organization', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    organization.value = response.data.data ?? response.data;
    logoPreview.value = organization.value.logo || '';
    logoFile.value = null;
    alert.success('Organization settings updated');
  } catch (error) {
    const validationErrors = error.response?.data?.errors;
    const firstValidation = validationErrors ? Object.values(validationErrors).flat()[0] : null;
    alert.error(firstValidation || error.response?.data?.message || 'Unable to update organization settings');
  } finally {
    saving.value = false;
  }
}
</script>
