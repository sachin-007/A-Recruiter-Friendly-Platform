import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Layouts
import DefaultLayout from '../layouts/DefaultLayout.vue';

// Pages
import Login from '../pages/auth/Login.vue';
import VerifyOtp from '../pages/auth/VerifyOtp.vue';
import Dashboard from '../pages/dashboard/Dashboard.vue';
import QuestionsIndex from '../pages/questions/Index.vue';
import QuestionForm from '../pages/questions/Form.vue';
import TestsIndex from '../pages/tests/Index.vue';
import TestForm from '../pages/tests/Form.vue';
import TestShow from '../pages/tests/Show.vue';
import InvitationsIndex from '../pages/invitations/Index.vue';
import SendInvitation from '../pages/invitations/Send.vue';
import CandidateTest from '../pages/attempts/CandidateTest.vue';
import ReportShow from '../pages/reports/Show.vue';
import AdminUsers from '../pages/admin/Users.vue';

const routes = [
    {
        path: '/',
        redirect: '/login',
        meta: { guest: true },
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/verify-otp',
        name: 'verify-otp',
        component: VerifyOtp,
        meta: { guest: true },
    },
    {
        path: '/test/:token',
        name: 'candidate-test',
        component: CandidateTest,
        meta: { requiresAuth: true, roles: ['candidate'] },
        props: true,
    },
    {
        path: '/',
        component: DefaultLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: 'dashboard',
                name: 'dashboard',
                component: Dashboard,
                meta: { roles: ['admin', 'recruiter', 'author'] },
            },
            // Questions
            {
                path: 'questions',
                name: 'questions.index',
                component: QuestionsIndex,
                meta: { roles: ['admin', 'recruiter', 'author'] },
            },
            {
                path: 'questions/create',
                name: 'questions.create',
                component: QuestionForm,
                meta: { roles: ['admin', 'author'] },
            },
            {
                path: 'questions/:id/edit',
                name: 'questions.edit',
                component: QuestionForm,
                meta: { roles: ['admin', 'author'] },
                props: true,
            },
            // Tests
            {
                path: 'tests',
                name: 'tests.index',
                component: TestsIndex,
                meta: { roles: ['admin', 'recruiter', 'author'] },
            },
            {
                path: 'tests/create',
                name: 'tests.create',
                component: TestForm,
                meta: { roles: ['admin', 'author'] },
            },
            {
                path: 'tests/:id',
                name: 'tests.show',
                component: TestShow,
                meta: { roles: ['admin', 'recruiter', 'author'] },
                props: true,
            },
            {
                path: 'tests/:id/edit',
                name: 'tests.edit',
                component: TestForm,
                meta: { roles: ['admin', 'author'] },
                props: true,
            },
            // Invitations
            {
                path: 'invitations',
                name: 'invitations.index',
                component: InvitationsIndex,
                meta: { roles: ['admin', 'recruiter'] },
            },
            {
                path: 'invitations/send',
                name: 'invitations.send',
                component: SendInvitation,
                meta: { roles: ['admin', 'recruiter'] },
            },
            // Reports
            {
                path: 'reports/attempt/:id',
                name: 'reports.show',
                component: ReportShow,
                meta: { roles: ['admin', 'recruiter'] },
                props: true,
            },
            // Admin
            {
                path: 'admin/users',
                name: 'admin.users',
                component: AdminUsers,
                meta: { roles: ['admin'] },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    if (auth.token && !auth.user) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth) {
        if (!auth.isAuthenticated) {
            next('/login');
        } else if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
            next('/dashboard'); // or 403 page
        } else {
            next();
        }
    } else if (to.meta.guest && auth.isAuthenticated) {
        if (auth.role === 'candidate' && auth.attempt) {
            next(`/test/${auth.attempt.invitation.token}`);
        } else {
            next('/dashboard');
        }
    } else {
        next();
    }
});

export default router;