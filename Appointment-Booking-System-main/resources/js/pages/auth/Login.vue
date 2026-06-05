<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const email = ref('');
const password = ref('');
const errors = ref({});
const generalError = ref('');
const isLoading = ref(false);

const autoFillDemo = () => {
    email.value = 'admin@example.com';
    password.value = 'password';
    generalError.value = '';
    errors.value = {};
};

const handleLogin = async () => {
    isLoading.value = true;
    errors.value = {};
    generalError.value = '';
    try {
        const response = await axios.post('/api/login', {
            email: email.value,
            password: password.value
        });
        localStorage.setItem('auth', 'true');
        localStorage.setItem('user', JSON.stringify(response.data.user));
        router.push({ name: 'admin.dashboard' });
    } catch (error) {
        console.error('Login error:', error);
        if (error.response && error.response.status === 422) {
            const errData = error.response.data.errors;
            if (errData.email && errData.email[0].includes('credentials do not match')) {
                generalError.value = errData.email[0];
            } else {
                errors.value = errData;
            }
        } else if (error.response && error.response.status === 419) {
            generalError.value = 'Session expired. Please refresh the page and try again.';
        } else if (error.response && error.response.data && error.response.data.message) {
            generalError.value = error.response.data.message;
        } else {
            generalError.value = 'Error: ' + (error.message || 'Connection failed.') + (error.response ? ' (Status: ' + error.response.status + ')' : ' (Network Error)');
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
                <div v-if="generalError" class="alert-premium-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ generalError }}</span>
                </div>

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

                <button type="button" @click="autoFillDemo" class="btn-glass-secondary mb-3">
                    <i class="fas fa-magic text-primary-color"></i> Auto-fill Demo Admin
                </button>

                <p class="auth-footer text-center mt-3 mb-0">
                    Don't have an account? 
                    <router-link to="/register" class="auth-link">Register</router-link>
                </p>
            </form>
        </div>
    </div>
</template>

<style scoped>
.auth-wrapper {
    min-height: 100vh;
    background-color: var(--bg-dark-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    padding: 2rem 1rem;
    z-index: 1;
}

.glow-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(120px);
    opacity: 0.12;
    z-index: 0;
    pointer-events: none;
}

.blob-1 {
    top: 15%;
    left: 20%;
    width: 300px;
    height: 300px;
    background: var(--primary-color);
}

.blob-2 {
    bottom: 15%;
    right: 20%;
    width: 350px;
    height: 350px;
    background: #8b5cf6;
}

.auth-card {
    width: 100%;
    max-width: 440px;
    background: rgba(17, 24, 39, 0.7) !important;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: var(--border-radius-lg) !important;
    z-index: 10;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
    position: relative;
}

.logo-circle {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: rgba(99, 102, 241, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(99, 102, 241, 0.2);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
}

.text-gradient {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 24px;
}

.auth-title {
    font-weight: 800;
    font-size: 28px;
    letter-spacing: -0.75px;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.auth-subtitle {
    color: var(--text-secondary);
    font-size: 14.5px;
    font-weight: 500;
}

.input-group-custom {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.input-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: var(--transition-fast);
}

.form-control-custom {
    width: 100%;
    padding: 13px 16px 13px 48px !important;
    background-color: rgba(31, 41, 55, 0.4) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: var(--text-primary) !important;
    border-radius: var(--border-radius-md) !important;
    font-size: 14.5px !important;
    height: 48px;
    transition: var(--transition-fast);
    outline: none !important;
}

.form-control-custom:focus {
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
    background-color: rgba(31, 41, 55, 0.6) !important;
}

.input-group-custom:focus-within .input-icon {
    color: var(--primary-color);
}

.form-control-custom.is-invalid {
    border-color: #ef4444 !important;
}

.error-feedback {
    color: #f87171;
    font-size: 12.5px;
    margin-top: 6px;
    display: block;
    font-weight: 500;
    animation: fadeInError 0.2s ease-in-out;
}

@keyframes fadeInError {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-premium-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #f87171;
    border-radius: var(--border-radius-md);
    padding: 12px 16px;
    font-size: 13.5px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    font-weight: 500;
    animation: fadeInError 0.2s ease-in-out;
}

.auth-footer {
    color: var(--text-secondary);
    font-size: 14px;
    font-weight: 500;
}

.auth-link {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition-fast);
}

.auth-link:hover {
    color: #8b5cf6;
    text-decoration: none;
}

.btn-glass-secondary {
    background: rgba(255, 255, 255, 0.03) !important;
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    color: var(--text-secondary) !important;
    border-radius: var(--border-radius-md) !important;
    font-weight: 600;
    font-size: 14px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: var(--transition-fast);
    cursor: pointer;
    width: 100%;
}

.btn-glass-secondary:hover {
    background: rgba(255, 255, 255, 0.06) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: var(--text-primary) !important;
}
</style>
