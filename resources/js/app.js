import './bootstrap';
import 'admin-lte/dist/js/adminlte.min.js';
import 'admin-lte/plugins/jquery/jquery.min.js';
import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js';
import { createApp } from 'vue/dist/vue.esm-bundler.js';
import router from './route.js';
import axios from 'axios';
import CommandSearch from './components/CommandSearch.vue';

const app = createApp({
    data() {
        return {
            currentUser: null,
            isResettingDemo: false,
            isLoggingOut: false
        };
    },
    created() {
        this.updateCurrentUser();
    },
    methods: {
        updateCurrentUser() {
            const userStr = localStorage.getItem('user');
            if (userStr) {
                try {
                    this.currentUser = JSON.parse(userStr);
                } catch (e) {
                    this.currentUser = null;
                }
            } else {
                this.currentUser = null;
            }
        },
        resetDemoData() {
            if (this.isResettingDemo) return;
            if (confirm('Are you sure you want to reset the demo workspace? All created contacts, deals, and bookings will be wiped and recreated.')) {
                this.isResettingDemo = true;
                axios.post('/api/demo/reset')
                    .then(response => {
                        this.isResettingDemo = false;
                        alert('Demo workspace data has been successfully reset!');
                        window.location.reload();
                    })
                    .catch(error => {
                        this.isResettingDemo = false;
                        alert('Failed to reset demo workspace: ' + (error.response?.data?.message || error.message));
                    });
            }
        },
        getInitials(name) {
            if (!name) return 'A';
            return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
        },
        logout() {
            this.isLoggingOut = true;
            axios.post('/api/logout')
                .then(() => {
                    setTimeout(() => {
                        this.clearSession();
                    }, 800);
                })
                .catch(() => {
                    this.clearSession();
                });
        },
        clearSession() {
            localStorage.removeItem('auth');
            localStorage.removeItem('user');
            this.currentUser = null;
            window.location.href = '/login';
        }
    },
    watch: {
        '$route'() {
            this.updateCurrentUser();
        }
    }
});

app.component('command-search', CommandSearch);
app.use(router);

app.mount("#app");