import { defineStore } from 'pinia';

export const useAlertStore = defineStore('alert', {
    state: () => ({
        type: null,
        message: null,
    }),
    actions: {
        success(message) {
            this.type = 'success';
            this.message = message;
        },
        error(message) {
            this.type = 'danger';
            this.message = message;
        },
        clear() {
            this.type = null;
            this.message = null;
        },
    },
});