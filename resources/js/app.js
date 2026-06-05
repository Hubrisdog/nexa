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
            currentUser: null
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
        getInitials(name) {
            if (!name) return 'A';
            return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
        },
        logout() {
            axios.post('/api/logout')
                .then(() => {
                    this.clearSession();
                })
                .catch(() => {
                    this.clearSession();
                });
        },
        clearSession() {
            localStorage.removeItem('auth');
            localStorage.removeItem('user');
            this.currentUser = null;
            router.push({ name: 'login' });
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