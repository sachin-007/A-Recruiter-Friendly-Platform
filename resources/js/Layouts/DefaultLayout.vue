<template>
  <div class="min-h-screen bg-slate-100">
    <header class="border-b border-slate-200 bg-white/95 backdrop-blur">
      <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">
            AP
          </div>
          <div>
            <h1 class="text-base font-semibold text-slate-900">Assessment Platform</h1>
            <p class="text-xs text-slate-500">Role: {{ user?.role || '-' }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <p class="hidden text-sm text-slate-600 sm:block">{{ user?.name }}</p>
          <button @click="logout" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Logout
          </button>
        </div>
      </div>

      <div class="mx-auto max-w-7xl overflow-x-auto px-4 pb-3 sm:px-6 lg:px-8">
        <nav class="flex min-w-max items-center gap-2">
          <router-link to="/dashboard" class="nav-pill" active-class="nav-pill-active">Dashboard</router-link>
          <router-link v-if="canViewQuestions" to="/questions" class="nav-pill" active-class="nav-pill-active">Questions</router-link>
          <router-link v-if="canImportQuestions" to="/questions/import" class="nav-pill" active-class="nav-pill-active">CSV Import</router-link>
          <router-link v-if="canViewTests" to="/tests" class="nav-pill" active-class="nav-pill-active">Tests</router-link>
          <router-link v-if="canInvite" to="/invitations" class="nav-pill" active-class="nav-pill-active">Invitations</router-link>
          <router-link v-if="canViewReports" to="/reports" class="nav-pill" active-class="nav-pill-active">Reports</router-link>
          <router-link v-if="isAdmin" to="/admin/users" class="nav-pill" active-class="nav-pill-active">Users</router-link>
          <router-link v-if="isSuperAdmin" to="/admin/organizations" class="nav-pill" active-class="nav-pill-active">Organizations</router-link>
          <router-link v-if="isAdmin" to="/admin/organization" class="nav-pill" active-class="nav-pill-active">My Organization</router-link>
        </nav>
      </div>
    </header>

    <main class="py-6">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const user = computed(() => auth.user);
const isSuperAdmin = computed(() => auth.role === 'super_admin');
const isAdmin = computed(() => ['super_admin', 'admin'].includes(auth.role));
const canViewQuestions = computed(() => ['super_admin', 'admin', 'author', 'recruiter'].includes(auth.role));
const canImportQuestions = computed(() => ['super_admin', 'admin', 'author'].includes(auth.role));
const canViewTests = computed(() => ['super_admin', 'admin', 'author', 'recruiter'].includes(auth.role));
const canInvite = computed(() => ['super_admin', 'admin', 'recruiter'].includes(auth.role));
const canViewReports = computed(() => ['super_admin', 'admin', 'recruiter'].includes(auth.role));

async function logout() {
  await auth.logout();
  router.push('/login');
}
</script>
