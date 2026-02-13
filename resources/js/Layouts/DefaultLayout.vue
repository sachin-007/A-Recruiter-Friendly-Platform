<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold">Assessment Platform</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link to="/dashboard" class="nav-link" active-class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                Dashboard
              </router-link>
              <router-link v-if="canViewQuestions" to="/questions" class="nav-link" active-class="border-indigo-500 text-gray-900">
                Questions
              </router-link>
              <router-link v-if="canViewTests" to="/tests" class="nav-link" active-class="border-indigo-500 text-gray-900">
                Tests
              </router-link>
              <router-link v-if="canInvite" to="/invitations" class="nav-link" active-class="border-indigo-500 text-gray-900">
                Invitations
              </router-link>
              <router-link v-if="isAdmin" to="/admin/users" class="nav-link" active-class="border-indigo-500 text-gray-900">
                Users
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <span class="text-sm text-gray-700 mr-4">{{ user?.name }} ({{ user?.role }})</span>
            <button @click="logout" class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
          </div>
        </div>
      </div>
    </nav>

    <main class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <Alert />
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import Alert from '../components/Alert.vue';

const auth = useAuthStore();
const router = useRouter();
const user = computed(() => auth.user);
const isAdmin = computed(() => auth.role === 'admin');
const canViewQuestions = computed(() => ['admin', 'author', 'recruiter'].includes(auth.role));
const canViewTests = computed(() => ['admin', 'author', 'recruiter'].includes(auth.role));
const canInvite = computed(() => ['admin', 'recruiter'].includes(auth.role));

async function logout() {
  await auth.logout();
  router.push('/login');
}
</script>