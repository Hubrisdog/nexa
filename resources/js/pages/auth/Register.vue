<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const company_name = ref('');
const slug = ref('');
const name = ref('');
const email = ref('');
const phone = ref('');
const password = ref('');
const password_confirmation = ref('');
const errors = ref({});
const generalError = ref('');
const isLoading = ref(false);

const handleRegister = async () => {
    isLoading.value = true;
    errors.value = {};
    generalError.value = '';
    try {
        const response = await axios.post('/api/register', {
            company_name: company_name.value,
            slug: slug.value,
            name: name.value,
            email: email.value,
            phone: phone.value,
            password: password.value,
            password_confirmation: password_confirmation.value
        });
        localStorage.setItem('auth', 'true');
        localStorage.setItem('user', JSON.stringify(response.data.user));
        router.push({ name: 'admin.dashboard' });
    } catch (error) {
        console.error('Registration error:', error);
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
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

const updateSlug = () => {
    // Generate clean URL slug automatically from company name
    slug.value = company_name.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '');
};
</script>

<template>
    <div class="auth-wrapper d-flex align-items-center justify-content-center">
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>

        <div class="auth-card glass-card p-5">
            <div class="auth-header text-center mb-4">
                <div class="logo-circle mb-3 mx-auto">
                    <i class="fas fa-building text-gradient"></i>
                </div>
                <h2 class="auth-title">Nexa</h2>
                <p class="auth-subtitle">Register your business account</p>
            </div>

            <form @submit.prevent="handleRegister" class="auth-form">
                <div v-if="generalError" class="alert-premium-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ generalError }}</span>
                </div>

                <!-- Section 1: Business Information -->
                <div class="mb-4">
                    <h6 class="text-xs text-uppercase font-weight-bold text-muted mb-3 border-bottom pb-2" style="border-color: var(--border-dark) !important;">Business Details</h6>
                    
                    <div class="form-group mb-3">
                        <label for="company_name" class="form-label">Company Name</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="far fa-building"></i></span>
                            <input 
                                v-model="company_name" 
                                @input="updateSlug"
                                type="text" 
                                id="company_name" 
                                class="form-control-custom" 
                                :class="{ 'is-invalid': errors.company_name }"
                                placeholder="My Business LLC" 
                                required
                            >
                        </div>
                        <div v-if="errors.company_name" class="error-feedback">{{ errors.company_name[0] }}</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="slug" class="form-label">Booking URL Slug</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fas fa-link"></i></span>
                            <span class="input-prefix text-secondary text-xs pl-2 d-none d-sm-inline">nexa.com/book/</span>
                            <input 
                                v-model="slug" 
                                type="text" 
                                id="slug" 
                                class="form-control-custom" 
                                :class="{ 'is-invalid': errors.slug }"
                                placeholder="my-business" 
                                required
                            >
                        </div>
                        <div v-if="errors.slug" class="error-feedback">{{ errors.slug[0] }}</div>
                    </div>
                </div>

                <!-- Section 2: Account Owner Credentials -->
                <div class="mb-4">
                    <h6 class="text-xs text-uppercase font-weight-bold text-muted mb-3 border-bottom pb-2" style="border-color: var(--border-dark) !important;">Owner Account Details</h6>

                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="far fa-user"></i></span>
                            <input 
                                v-model="name" 
                                type="text" 
                                id="name" 
                                class="form-control-custom" 
                                :class="{ 'is-invalid': errors.name }"
                                placeholder="John Doe" 
                                required
                            >
                        </div>
                        <div v-if="errors.name" class="error-feedback">{{ errors.name[0] }}</div>
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
                                placeholder="owner@company.com" 
                                required
                            >
                        </div>
                        <div v-if="errors.email" class="error-feedback">{{ errors.email[0] }}</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fas fa-phone-alt"></i></span>
                            <input 
                                v-model="phone" 
                                type="tel" 
                                id="phone" 
                                class="form-control-custom" 
                                :class="{ 'is-invalid': errors.phone }"
                                placeholder="+1 (555) 000-0000"
                            >
                        </div>
                        <div v-if="errors.phone" class="error-feedback">{{ errors.phone[0] }}</div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input 
                                v-model="password" 
                                type="password" 
                                id="password" 
                                class="form-control-custom" 
                                :class="{ 'is-invalid': errors.password }"
                                placeholder="Min. 8 characters" 
                                required
                            >
                        </div>
                        <div v-if="errors.password" class="error-feedback">{{ errors.password[0] }}</div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group-custom">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input 
                                v-model="password_confirmation" 
                                type="password" 
                                id="password_confirmation" 
                                class="form-control-custom" 
                                placeholder="Repeat password" 
                                required
                            >
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-gradient w-100 py-3 mb-3" :disabled="isLoading">
                    <span v-if="isLoading"><i class="fas fa-spinner fa-spin mr-2"></i> Creating SaaS Workspace...</span>
                    <span v-else>Register Business</span>
                </button>

                <p class="auth-footer text-center mt-3 mb-0">
                    Already have a SaaS account? 
                    <router-link to="/login" class="auth-link">Sign In</router-link>
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
    padding: 3rem 1rem;
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
    top: 10%;
    left: 15%;
    width: 350px;
    height: 350px;
    background: var(--primary-color);
}

.blob-2 {
    bottom: 10%;
    right: 15%;
    width: 400px;
    height: 400px;
    background: #8b5cf6;
}

.auth-card {
    width: 100%;
    max-width: 520px;
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
    font-size: 14px !important;
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

.input-prefix {
    position: absolute;
    left: 45px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    z-index: 10;
    font-size: 13px;
    color: var(--text-muted);
    font-weight: 500;
}

#slug {
    padding-left: 48px !important;
}

@media (min-width: 576px) {
    #slug {
        padding-left: 150px !important;
    }
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

.btn-gradient {
    background: var(--primary-gradient) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: var(--border-radius-md) !important;
    font-weight: 600;
    font-size: 14.5px;
    padding: 12px 20px;
    transition: var(--transition-fast);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}

.btn-gradient:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(99, 102, 241, 0.35);
}
</style>
