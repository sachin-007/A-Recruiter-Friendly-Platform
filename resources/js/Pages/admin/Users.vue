<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">User Management</h1>
        <p class="text-sm text-slate-500">Create users, assign roles, and control active status.</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary">
        Add User
      </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total Users</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ users.data.length }}</p>
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

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
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
            <td class="px-4 py-3">
              <select
                :value="user.role"
                class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
                @change="updateRole(user, $event.target.value)"
              >
                <option value="admin">Admin</option>
                <option value="recruiter">Recruiter</option>
                <option value="author">Author</option>
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
          <tr v-if="!users.data.length">
            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">No users found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :show="showCreateModal" @close="showCreateModal = false">
      <div class="p-5">
        <h2 class="text-lg font-semibold text-slate-900">Add New User</h2>
        <p class="mt-1 text-sm text-slate-500">Create team member access with role assignment.</p>

        <form class="mt-4 space-y-3" @submit.prevent="createUser">
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
              <option value="recruiter">Recruiter</option>
              <option value="author">Author</option>
              <option value="admin">Admin</option>
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
import api from '../../utils/axios';

const alert = useAlertStore();
const users = ref({ data: [] });
const showCreateModal = ref(false);
const creating = ref(false);
const newUser = ref({
  name: '',
  email: '',
  role: 'recruiter',
  is_active: true,
});

const activeCount = computed(() => users.value.data.filter((user) => user.is_active).length);
const inactiveCount = computed(() => users.value.data.filter((user) => !user.is_active).length);

onMounted(fetchUsers);

async function fetchUsers() {
  const response = await api.get('/users', { params: { per_page: 200 } });
  users.value = response.data;
}

async function createUser() {
  creating.value = true;
  try {
    await api.post('/users', newUser.value);
    alert.success('User created');
    showCreateModal.value = false;
    newUser.value = { name: '', email: '', role: 'recruiter', is_active: true };
    await fetchUsers();
  } catch (error) {
    const message = error.response?.data?.message || 'Unable to create user';
    alert.error(message);
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
    await fetchUsers();
  } catch (error) {
    alert.error(error.response?.data?.message || 'Unable to delete user');
  }
}
</script>
