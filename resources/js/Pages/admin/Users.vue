<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">User Management</h1>
        <p class="text-sm text-slate-500">Create users, assign roles, and control active status.</p>
      </div>
      <button @click="openCreateModal" class="btn-primary">
        Add User
      </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Visible Users</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ users.data?.length || 0 }}</p>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Active</p>
        <p class="mt-1 text-2xl font-semibold text-emerald-600">{{ activeCount }}</p>
      </article>
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Inactive</p>
        <p class="mt-1 text-2xl font-semibold text-rose-600">{{ inactiveCount }}</p>
      </article>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="grid gap-3 md:grid-cols-3">
        <div>
          <label class="field-label">Role Filter</label>
          <select v-model="filters.role" class="field-input" @change="fetchUsers(1)">
            <option value="">All Roles</option>
            <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
          </select>
        </div>
        <div v-if="isSuperAdmin">
          <label class="field-label">Organization Filter</label>
          <select v-model="filters.organization_id" class="field-input" @change="fetchUsers(1)">
            <option value="">All Organizations</option>
            <option v-for="organization in organizations" :key="organization.id" :value="organization.id">
              {{ organization.name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
            <th v-if="isSuperAdmin" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Organization</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr v-for="user in users.data" :key="user.id">
            <td class="px-4 py-3">
              <p class="text-sm font-medium text-slate-900">{{ user.name }}</p>
              <p class="text-xs text-slate-500">{{ user.email }}</p>
            </td>

            <td v-if="isSuperAdmin" class="px-4 py-3">
              <select
                class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
                :value="user.organization_id"
                @change="updateOrganization(user, $event.target.value)"
              >
                <option v-for="organization in organizations" :key="organization.id" :value="organization.id">
                  {{ organization.name }}
                </option>
              </select>
            </td>

            <td class="px-4 py-3">
              <select
                :value="user.role"
                class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
                @change="updateRole(user, $event.target.value)"
              >
                <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>
            </td>

            <td class="px-4 py-3">
              <span
                :class="user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                class="rounded-full px-2 py-1 text-xs font-semibold"
              >
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>

            <td class="px-4 py-3 text-right">
              <button
                class="mr-3 text-sm font-medium text-indigo-600 hover:text-indigo-800"
                @click="toggleActive(user)"
              >
                {{ user.is_active ? 'Deactivate' : 'Activate' }}
              </button>
              <button class="text-sm font-medium text-rose-600 hover:text-rose-800" @click="deleteUser(user)">
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="!users.data?.length">
            <td :colspan="isSuperAdmin ? 5 : 4" class="px-4 py-6 text-center text-sm text-slate-500">No users found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :show="showCreateModal" @close="showCreateModal = false">
      <div class="p-5">
        <h2 class="text-lg font-semibold text-slate-900">Add New User</h2>
        <p class="mt-1 text-sm text-slate-500">Create team member access with role assignment.</p>

        <form class="mt-4 space-y-3" @submit.prevent="createUser">
          <div v-if="isSuperAdmin">
            <label class="field-label">Organization</label>
            <select v-model="newUser.organization_id" required class="field-input">
              <option value="" disabled>Select organization</option>
              <option v-for="organization in organizations" :key="organization.id" :value="organization.id">
                {{ organization.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="field-label">Name</label>
            <input v-model.trim="newUser.name" type="text" required class="field-input" />
          </div>
          <div>
            <label class="field-label">Email</label>
            <input v-model.trim="newUser.email" type="email" required class="field-input" />
          </div>
          <div>
            <label class="field-label">Role</label>
            <select v-model="newUser.role" required class="field-input">
              <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="newUser.is_active" type="checkbox" />
            Active user
          </label>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="btn-secondary" @click="showCreateModal = false">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="creating">
              {{ creating ? 'Creating...' : 'Create User' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Modal from '../../Components/Modal.vue';
import { useAlertStore } from '../../stores/alert';
import { useAuthStore } from '../../stores/auth';
import api from '../../utils/axios';

const alert = useAlertStore();
const auth = useAuthStore();

const users = ref({ data: [] });
const organizations = ref([]);
const showCreateModal = ref(false);
const creating = ref(false);
const filters = ref({
  role: '',
  organization_id: '',
});

const newUser = ref({
  organization_id: '',
  name: '',
  email: '',
  role: 'recruiter',
  is_active: true,
});

const isSuperAdmin = computed(() => auth.role === 'super_admin');
const roleOptions = computed(() => {
  const baseRoles = [
    { value: 'admin', label: 'Admin' },
    { value: 'recruiter', label: 'Recruiter' },
    { value: 'author', label: 'Author' },
  ];

  if (isSuperAdmin.value) {
    return [{ value: 'super_admin', label: 'Super Admin' }, ...baseRoles];
  }

  return baseRoles;
});

const activeCount = computed(() => users.value.data?.filter((user) => user.is_active).length || 0);
const inactiveCount = computed(() => users.value.data?.filter((user) => !user.is_active).length || 0);

onMounted(async () => {
  if (isSuperAdmin.value) {
    await fetchOrganizations();
  }
  await fetchUsers();
});

async function fetchOrganizations() {
  try {
    const response = await api.get('/organizations', { params: { per_page: 200 } });
    organizations.value = response.data.data || [];

    if (!newUser.value.organization_id && organizations.value.length) {
      newUser.value.organization_id = organizations.value[0].id;
    }
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to load organizations');
  }
}

async function fetchUsers(page = 1) {
  try {
    const response = await api.get('/users', {
      params: {
        page,
        per_page: 200,
        role: filters.value.role || undefined,
        organization_id: isSuperAdmin.value ? (filters.value.organization_id || undefined) : undefined,
      },
    });
    users.value = response.data;
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to load users');
  }
}

function openCreateModal() {
  newUser.value = {
    organization_id: filters.value.organization_id || organizations.value[0]?.id || '',
    name: '',
    email: '',
    role: roleOptions.value.find((role) => role.value === 'recruiter')?.value || roleOptions.value[0]?.value || 'recruiter',
    is_active: true,
  };
  showCreateModal.value = true;
}

async function createUser() {
  creating.value = true;
  try {
    const payload = {
      name: newUser.value.name,
      email: newUser.value.email,
      role: newUser.value.role,
      is_active: newUser.value.is_active,
    };

    if (isSuperAdmin.value) {
      payload.organization_id = newUser.value.organization_id;
    }

    await api.post('/users', payload);
    alert.success('User created');
    showCreateModal.value = false;
    await fetchUsers(users.value.current_page || 1);
  } catch (error) {
    const validationErrors = error.response?.data?.errors;
    const firstValidation = validationErrors ? Object.values(validationErrors).flat()[0] : null;
    alert.error(firstValidation || error.response?.data?.message || 'Unable to create user');
  } finally {
    creating.value = false;
  }
}

async function updateRole(user, role) {
  if (user.role === role) return;

  try {
    await api.put(`/users/${user.id}`, { role });
    user.role = role;
    alert.success('Role updated');
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to update role');
  }
}

async function updateOrganization(user, organizationId) {
  if (user.organization_id === organizationId) return;

  try {
    await api.put(`/users/${user.id}`, { organization_id: organizationId });
    user.organization_id = organizationId;
    alert.success('Organization updated');
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to update organization');
    await fetchUsers(users.value.current_page || 1);
  }
}

async function toggleActive(user) {
  try {
    await api.put(`/users/${user.id}/toggle-active`);
    user.is_active = !user.is_active;
    alert.success(`User ${user.is_active ? 'activated' : 'deactivated'}`);
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to change user status');
  }
}

async function deleteUser(user) {
  if (!window.confirm(`Delete user ${user.name}?`)) return;

  try {
    await api.delete(`/users/${user.id}`);
    alert.success('User deleted');
    await fetchUsers(users.value.current_page || 1);
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to delete user');
  }
}
</script>
