<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const email = ref('');
const password = ref('');
const errors = ref({});
const isLoading = ref(false);

const autoFillDemo = () => {
    email.value = 'admin@example.com';
    password.value = 'password';
};

const handleLogin = async () => {
    isLoading.value = true;
    errors.value = {};
    try {
        const response = await axios.post('/api/login', {
            email: email.value,
            password: password.value
        });
        localStorage.setItem('auth', 'true');
        localStorage.setItem('user', JSON.stringify(response.data.user));
        router.push({ name: 'admin.dashboard' });
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else if (error.response && error.response.data && error.response.data.message) {
            errors.value = { email: [error.response.data.message] };
        } else {
            errors.value = { email: ['Something went wrong. Please try again.'] };
        }
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="auth-wrapper d-flex align-items-center justify-content-center">
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>

        <div class="auth-card glass-card p-5">
            <div class="auth-header text-center mb-4">
                <div class="logo-circle mb-3 mx-auto">
                    <i class="fas fa-calendar-alt text-gradient"></i>
                </div>
                <h2 class="auth-title">Nexa</h2>
                <p class="auth-subtitle">Sign in to your administration dashboard</p>
            </div>

            <form @submit.prevent="handleLogin" class="auth-form">
                <div class="form-group mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="far fa-envelope"></i></span>
                        <input 
                            v-model="email" 
                            type="email" 
                            id="email" 
                            class="form-control-custom" 
                            :class="{ 'is-invalid': errors.email }"
                            placeholder="name@example.com" 
                            required
                        >
                    </div>
                    <div v-if="errors.email" class="error-feedback">{{ errors.email[0] }}</div>
                </div>

                <div class="form-group mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input 
                            v-model="password" 
                            type="password" 
                            id="password" 
                            class="form-control-custom" 
                            :class="{ 'is-invalid': errors.password }"
                            placeholder="••••••••" 
                            required
                        >
                    </div>
                    <div v-if="errors.password" class="error-feedback">{{ errors.password[0] }}</div>
                </div>

                <button type="submit" class="btn-gradient w-100 py-3 mb-3" :disabled="isLoading">
                    <span v-if="isLoading"><i class="fas fa-spinner fa-spin mr-2"></i> Signing In...</span>
                    <span v-else>Sign In</span>
                </button>

                <button type="button" @click="autoFillDemo" class="btn btn-outline-secondary w-100 py-2 mb-3" style="border-radius: 8px; font-weight: 500; font-size: 14px;">
                    <i class="fas fa-magic mr-1 text-primary"></i> Auto-fill Demo Admin
                </button>

                <p class="auth-footer text-center mt-3 mb-0">
                    Don't have an account? 
                    <router-link to="/register" class="auth-link">Register</router-link>
                </p>
            </form>
        </div>
    </div>
</template>
