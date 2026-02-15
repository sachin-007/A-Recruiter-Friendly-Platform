<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Organizations</h1>
        <p class="text-sm text-slate-500">Manage all organizations across the platform.</p>
      </div>
      <button class="btn-primary" @click="openCreateModal">
        Add Organization
      </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total Organizations</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ organizations.total || 0 }}</p>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total Users</p>
        <p class="mt-1 text-2xl font-semibold text-indigo-700">{{ totalUsers }}</p>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Completed Attempts</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ totalCompletedAttempts }}</p>
      </article>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Organization</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Users</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tests</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Completed Attempts</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Updated</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="organization in organizations.data" :key="organization.id">
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img
                  v-if="organization.logo"
                  :src="organization.logo"
                  alt="Organization logo"
                  class="h-9 w-9 rounded-md border border-slate-200 object-cover"
                />
                <div>
                  <p class="text-sm font-medium text-slate-900">{{ organization.name }}</p>
                  <p class="text-xs text-slate-500">{{ organization.id }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">{{ organization.users_count ?? 0 }}</td>
            <td class="px-4 py-3 text-sm text-slate-700">{{ organization.tests_count ?? 0 }}</td>
            <td class="px-4 py-3 text-sm text-slate-700">{{ organization.completed_attempts_count ?? 0 }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ formatDate(organization.updated_at) }}</td>
            <td class="px-4 py-3 text-right">
              <button class="text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="openEditModal(organization)">
                Edit
              </button>
            </td>
          </tr>
          <tr v-if="!organizations.data?.length">
            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
              No organizations found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :pagination="organizations" @page-change="fetchOrganizations" />

    <Modal :show="showModal" @close="closeModal">
      <div class="p-5">
        <h2 class="text-lg font-semibold text-slate-900">
          {{ editingOrganizationId ? 'Edit Organization' : 'Create Organization' }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
          Configure organization details and default notification settings.
        </p>

        <form class="mt-4 space-y-4" @submit.prevent="saveOrganization">
          <div>
            <label class="field-label">Organization Name</label>
            <input v-model.trim="form.name" type="text" class="field-input" required />
          </div>

          <div>
            <label class="field-label">Logo</label>
            <input type="file" accept="image/*" class="field-input" @change="onLogoChange" />
            <p class="mt-1 text-xs text-slate-500">PNG/JPG/WebP up to 2MB.</p>
          </div>

          <div v-if="logoPreview" class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Logo Preview</p>
            <img :src="logoPreview" alt="Logo preview" class="max-h-20 rounded border border-slate-200 bg-white p-1" />
          </div>

          <div class="rounded-lg border border-slate-200 p-4">
            <p class="text-sm font-semibold text-slate-800">Notifications</p>
            <div class="mt-2 space-y-2">
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

          <div class="flex justify-end gap-2">
            <button type="button" class="btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              {{ saving ? 'Saving...' : (editingOrganizationId ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Modal from '../../Components/Modal.vue';
import Pagination from '../../Components/Pagination.vue';
import { useAlertStore } from '../../stores/alert';
import api from '../../utils/axios';

const alert = useAlertStore();
const organizations = ref({ data: [] });
const showModal = ref(false);
const saving = ref(false);
const editingOrganizationId = ref(null);
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

const totalUsers = computed(() => organizations.value.data?.reduce((sum, item) => sum + Number(item.users_count || 0), 0) || 0);
const totalCompletedAttempts = computed(
  () => organizations.value.data?.reduce((sum, item) => sum + Number(item.completed_attempts_count || 0), 0) || 0
);

onMounted(() => {
  fetchOrganizations();
});

onUnmounted(() => {
  if (localPreviewUrl) {
    URL.revokeObjectURL(localPreviewUrl);
  }
});

async function fetchOrganizations(page = 1) {
  try {
    const response = await api.get('/organizations', { params: { page, per_page: 15 } });
    organizations.value = response.data;
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to load organizations');
  }
}

function openCreateModal() {
  editingOrganizationId.value = null;
  form.value = {
    name: '',
    settings: {
      notifications: {
        email: true,
        sms: false,
      },
    },
  };
  logoFile.value = null;
  logoPreview.value = '';
  showModal.value = true;
}

function openEditModal(organization) {
  editingOrganizationId.value = organization.id;
  form.value = {
    name: organization.name,
    settings: {
      notifications: {
        email: organization.settings?.notifications?.email ?? true,
        sms: organization.settings?.notifications?.sms ?? false,
      },
    },
  };
  logoFile.value = null;
  logoPreview.value = organization.logo || '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  logoFile.value = null;
}

function onLogoChange(event) {
  const file = event.target.files?.[0] || null;
  logoFile.value = file;

  if (!file) {
    return;
  }

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

async function saveOrganization() {
  saving.value = true;
  try {
    const payload = new FormData();
    payload.append('name', form.value.name || '');
    payload.append('settings[notifications][email]', form.value.settings.notifications.email ? '1' : '0');
    payload.append('settings[notifications][sms]', form.value.settings.notifications.sms ? '1' : '0');

    if (logoFile.value) {
      payload.append('logo', logoFile.value);
    }

    if (editingOrganizationId.value) {
      payload.append('_method', 'PUT');
      await api.post(`/organizations/${editingOrganizationId.value}`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      alert.success('Organization updated');
    } else {
      await api.post('/organizations', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      alert.success('Organization created');
    }

    closeModal();
    await fetchOrganizations(organizations.value.current_page || 1);
  } catch (error) {
    const validationErrors = error.response?.data?.errors;
    const firstValidation = validationErrors ? Object.values(validationErrors).flat()[0] : null;
    alert.error(firstValidation || error.response?.data?.message || 'Unable to save organization');
  } finally {
    saving.value = false;
  }
}

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleString();
}
</script>
