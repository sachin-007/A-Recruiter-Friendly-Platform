<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-semibold">User Management</h1>
      <button @click="showCreateModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
        Add User
      </button>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="user in users.data" :key="user.id">
            <td class="px-6 py-4">{{ user.name }}</td>
            <td class="px-6 py-4">{{ user.email }}</td>
            <td class="px-6 py-4">
              <select @change="updateRole(user, $event.target.value)" class="border rounded px-2 py-1 text-sm">
                <option value="admin" :selected="user.role === 'admin'">Admin</option>
                <option value="recruiter" :selected="user.role === 'recruiter'">Recruiter</option>
                <option value="author" :selected="user.role === 'author'">Author</option>
              </select>
            </td>
            <td class="px-6 py-4">
              <span :class="user.is_active ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100'" class="px-2 py-1 rounded text-xs">
                {{ user.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <button @click="toggleActive(user)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                {{ user.is_active ? 'Deactivate' : 'Activate' }}
              </button>
              <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create User Modal -->
    <Modal :show="showCreateModal" @close="showCreateModal = false">
      <h2 class="text-lg font-medium mb-4">Add New User</h2>
      <form @submit.prevent="createUser">
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Name</label>
          <input v-model="newUser.name" type="text" required class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Email</label>
          <input v-model="newUser.email" type="email" required class="w-full border rounded px-3 py-2">
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Role</label>
          <select v-model="newUser.role" required class="w-full border rounded px-3 py-2">
            <option value="recruiter">Recruiter</option>
            <option value="author">Author</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Create</button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAlertStore } from '../../stores/alert';
import Modal from '../../Components/Modal.vue';
import api from '../../utils/axios';

const alert = useAlertStore();
const users = ref({ data: [] });
const showCreateModal = ref(false);
const newUser = ref({ name: '', email: '', role: 'recruiter' });

const fetchUsers = async () => {
  const res = await api.get('/users');
  users.value = res.data;
};

const createUser = async () => {
  await api.post('/users', newUser.value);
  alert.success('User created');
  showCreateModal.value = false;
  newUser.value = { name: '', email: '', role: 'recruiter' };
  fetchUsers();
};

const updateRole = async (user, role) => {
  await api.put(`/users/${user.id}`, { role });
  alert.success('Role updated');
  user.role = role;
};

const toggleActive = async (user) => {
  await api.put(`/users/${user.id}/toggle-active`);
  user.is_active = !user.is_active;
  alert.success(`User ${user.is_active ? 'activated' : 'deactivated'}`);
};

const deleteUser = async (user) => {
  if (confirm(`Delete user ${user.name}?`)) {
    await api.delete(`/users/${user.id}`);
    alert.success('User deleted');
    fetchUsers();
  }
};

onMounted(fetchUsers);
</script>
