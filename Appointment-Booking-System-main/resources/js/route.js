import Dashboard from "./components/Dashboard.vue";
import Appointment from "./pages/appointments/Appointment.vue";
import User from "./pages/users/User.vue";
import Setting from "./pages/settings/Setting.vue";
import Profile from "./pages/profile/Profile.vue";
import Login from "./pages/auth/Login.vue";
import Register from "./pages/auth/Register.vue";
import CrmPipeline from "./pages/crm/CrmPipeline.vue";
import Analytics from "./pages/analytics/Analytics.vue";
import PublicBook from "./pages/booking/PublicBook.vue";
import { createRouter, createWebHistory } from "vue-router";
import axios from "axios";

const isWorkspaceDomain = () => {
    const host = window.location.hostname;
    const parts = host.split('.');
    
    if (host.endsWith('localhost') || host.includes('127.0.0.1')) {
        return parts.length > 1 && parts[0] !== '127' && parts[0] !== 'localhost';
    }
    
    if (parts.length >= 3) {
        return !['www', 'app', 'api'].includes(parts[0]);
    }
    
    if (parts.length >= 2 && !host.endsWith('nexa.co') && !host.endsWith('nexa.com')) {
        return true;
    }
    
    return false;
};

const routes = [
    {
        path: '/',
        name: 'workspace.root',
        component: PublicBook,
        meta: { guest: false, auth: false }
    },
    {
        path: '/book/:username',
        name: 'public.book',
        component: PublicBook,
        meta: { guest: false, auth: false }
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { guest: true }
    },
    {
        path: '/admin/dashboard',
        name: 'admin.dashboard',
        component: Dashboard,
        meta: { auth: true }
    },
    {
        path: '/admin/appointment',
        name: 'admin.appointment',
        component: Appointment,
        meta: { auth: true }
    },
    {
        path: '/admin/users',
        name: 'admin.users',
        component: User,
        meta: { auth: true }
    },
    {
        path: '/admin/settings',
        name: 'admin.settings',
        component: Setting,
        meta: { auth: true }
    },
    {
        path: '/admin/profile',
        name: 'admin.profile',
        component: Profile,
        meta: { auth: true }
    },
    {
        path: '/admin/crm',
        name: 'admin.crm',
        component: CrmPipeline,
        meta: { auth: true }
    },
    {
        path: '/admin/analytics',
        name: 'admin.analytics',
        component: Analytics,
        meta: { auth: true }
    }
];

let router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('auth') === 'true';
    const userStr = localStorage.getItem('user');
    let user = null;
    if (userStr) {
        try {
            user = JSON.parse(userStr);
        } catch (e) {}
    }

    if (to.path === '/') {
        if (!isWorkspaceDomain()) {
            next({ name: 'admin.dashboard' });
            return;
        }
    }

    if (to.matched.some(record => record.meta.auth)) {
        if (!isAuthenticated) {
            next({ name: 'login' });
        } else {
            if (to.path === '/admin/users' || to.path === '/admin/settings') {
                if (!user || user.role !== 'admin') {
                    next({ name: 'admin.dashboard' });
                    return;
                }
            }
            next();
        }
    } else if (to.matched.some(record => record.meta.guest)) {
        if (isAuthenticated) {
            next({ name: 'admin.dashboard' });
        } else {
            next();
        }
    } else {
        next();
    }
});

axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('auth');
            localStorage.removeItem('user');
            router.push({ name: 'login' });
        }
        return Promise.reject(error);
    }
);

export default router;