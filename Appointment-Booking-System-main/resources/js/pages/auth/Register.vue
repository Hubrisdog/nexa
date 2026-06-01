<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const name = ref('');
const email = ref('');
const phone = ref('');
const password = ref('');
const password_confirmation = ref('');
const errors = ref({});
const isLoading = ref(false);

const handleRegister = async () => {
    isLoading.value = true;
    errors.value = {};
    try {
        const response = await axios.post('/api/register', {
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
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
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
                    <i class="fas fa-user-plus text-gradient"></i>
                </div>
                <h2 class="auth-title">Nexa</h2>
                <p class="auth-subtitle">Create your client account</p>
            </div>

            <form @submit.prevent="handleRegister" class="auth-form">
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
                            placeholder="name@example.com" 
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

                <button type="submit" class="btn-gradient w-100 py-3 mb-3" :disabled="isLoading">
                    <span v-if="isLoading"><i class="fas fa-spinner fa-spin mr-2"></i> Creating Account...</span>
                    <span v-else>Register</span>
                </button>

                <p class="auth-footer text-center mt-3 mb-0">
                    Already have an account? 
                    <router-link to="/login" class="auth-link">Sign In</router-link>
                </p>
            </form>
        </div>
    </div>
</template>
