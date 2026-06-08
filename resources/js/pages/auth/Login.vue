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

const loginWithGoogle = () => {
    isLoading.value = true;
    window.location.href = '/demo';
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

                <div class="d-flex align-items-center my-4">
                    <hr class="flex-grow-1 border-secondary" style="opacity: 0.15;" />
                    <span class="mx-3 text-muted text-xs uppercase font-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Or continue with</span>
                    <hr class="flex-grow-1 border-secondary" style="opacity: 0.15;" />
                </div>

                <button type="button" @click="loginWithGoogle" class="btn-google w-100 mb-3" :disabled="isLoading">
                    <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Google</span>
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

@keyframes pulseBlob {
    0%, 100% { transform: scale(1); opacity: 0.12; }
    50% { transform: scale(1.15); opacity: 0.18; }
}

.blob-1 {
    top: 15%;
    left: 20%;
    width: 300px;
    height: 300px;
    background: var(--primary-color);
    animation: pulseBlob 8s ease-in-out infinite;
}

.blob-2 {
    bottom: 15%;
    right: 20%;
    width: 350px;
    height: 350px;
    background: #8b5cf6;
    animation: pulseBlob 10s ease-in-out infinite alternate;
}

.auth-card {
    width: 100%;
    max-width: 440px;
    background: rgba(13, 17, 28, 0.45) !important;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.08) !important;
    border-radius: var(--border-radius-lg) !important;
    z-index: 10;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.05);
    position: relative;
    padding: 3rem 2.5rem !important;
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
