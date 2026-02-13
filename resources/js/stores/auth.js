import { defineStore } from 'pinia';
import api from '../utils/axios';
import router from '../router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token') || null,
        attempt: null,
        invitation: null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        role: (state) => state.user?.role || null,
        isCandidate: (state) => state.user?.role === 'candidate' || !!state.attempt,
    },
    actions: {
        setToken(token) {
            this.token = token;
            localStorage.setItem('auth_token', token);
        },
        async sendOtp(email) {
            return api.post('/otp/send', { email });
        },
        async verifyOtp(email, otp) {
            const response = await api.post('/otp/verify', { email, otp });
            this.setToken(response.data.token);
            this.user = response.data.user;
            await router.push('/dashboard');
        },
        async verifyMagicLink(token) {
            const response = await api.post('/magic-link/verify', { token });
            this.setToken(response.data.token);
            this.attempt = response.data.attempt;
            this.invitation = response.data.invitation;
            this.user = {
                role: 'candidate',
                name: response.data.attempt?.candidate_name || response.data.attempt?.candidate_email || 'Candidate',
            };
            await router.push(`/test/${token}`);
        },
        async fetchUser() {
            if (!this.token) return;
            if (this.attempt && this.user?.role === 'candidate') return;
            try {
                const response = await api.get('/user');
                this.user = response.data;
            } catch (error) {
                this.logout();
            }
        },
        async logout() {
            if (this.token) {
                await api.post('/logout').catch(() => {});
            }
            localStorage.removeItem('auth_token');
            this.token = null;
            this.user = null;
            this.attempt = null;
            this.invitation = null;
            await router.push('/login');
        },
    },
    persist: true, // if using pinia-plugin-persistedstate
});
