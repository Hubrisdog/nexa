<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('details');

const form = ref({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: ''
});

const isSuccess = ref(false);
const isSubmitting = ref(false);
const errors = ref({});

const fetchProfile = async () => {
    try {
        const response = await axios.get('/api/profile');
        if (response.data) {
            form.value.name = response.data.name || '';
            form.value.email = response.data.email || '';
            form.value.phone = response.data.phone || '';
            form.value.password = '';
            form.value.password_confirmation = '';
        }
    } catch (error) {
        console.error("Error fetching profile details:", error);
    }
};

const handleSave = async () => {
    isSubmitting.value = true;
    isSuccess.value = false;
    errors.value = {};
    try {
        const response = await axios.put('/api/profile', {
            name: form.value.name,
            email: form.value.email,
            phone: form.value.phone,
            password: form.value.password || null,
            password_confirmation: form.value.password_confirmation || null
        });
        
        localStorage.setItem('user', JSON.stringify(response.data.user));
        
        isSuccess.value = true;
        form.value.password = '';
        form.value.password_confirmation = '';
        
        setTimeout(() => {
            isSuccess.value = false;
        }, 3000);
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error("Error updating profile:", error);
        }
    } finally {
        isSubmitting.value = false;
    }
};

const getInitials = (name) => {
    if (!name) return 'U';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

onMounted(() => {
    fetchProfile();
});
</script>

<template>
    <div class="content-header py-4">
        <div class="container-fluid">
            <h1 class="font-weight-extrabold mb-1 tracking-tight" style="font-size: 26px;">Account Profile</h1>
            <p class="text-muted mb-0 text-sm">Update your identity parameters, security password, and access preferences.</p>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Summary Card Left -->
                <div class="col-lg-4 mb-4">
                    <div class="card glass-card border-0 p-4 text-center">
                        <div class="avatar-initials avatar-initials-lg mx-auto mb-3" style="border: 4px solid #ffffff; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                            {{ getInitials(form.name) }}
                        </div>
                        <h5 class="font-weight-extrabold mb-1" style="color: #0f172a; font-size: 18px;">{{ form.name || 'Admin User' }}</h5>
                        <p class="text-primary small font-weight-bold mb-3"><i class="fas fa-shield-alt mr-1"></i> System Administrator</p>
                        
                        <div class="text-left mt-4 pt-3 border-top">
                            <div class="mb-3">
                                <span class="small text-muted d-block font-weight-bold">Email Address</span>
                                <span class="text-sm font-weight-semibold" style="color: #334155;">{{ form.email }}</span>
                            </div>
                            <div>
                                <span class="small text-muted d-block font-weight-bold">Phone Number</span>
                                <span class="text-sm font-weight-semibold" style="color: #334155;">{{ form.phone || 'Not provided' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Card Right -->
                <div class="col-lg-8 mb-4">
                    <div class="card glass-card border-0 p-4">
                        <div class="nav-tabs-custom">
                            <span @click="activeTab = 'details'" class="tab-link" :class="{ 'active': activeTab === 'details' }">
                                <i class="far fa-user mr-2"></i> Account details
                            </span>
                            <span @click="activeTab = 'security'" class="tab-link" :class="{ 'active': activeTab === 'security' }">
                                <i class="fas fa-key mr-2"></i> Security
                            </span>
                        </div>

                        <div v-if="isSuccess" class="alert alert-success mb-4" style="border-radius: 8px;">
                            <i class="fas fa-check-circle mr-2"></i> Profile details updated successfully.
                        </div>

                        <form @submit.prevent="handleSave">
                            <!-- Tab: Details -->
                            <div v-if="activeTab === 'details'" class="tab-body">
                                <div class="form-group mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input v-model="form.name" type="text" class="form-control-modern w-100" required>
                                    <div v-if="errors.name" class="error-feedback">{{ errors.name[0] }}</div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input v-model="form.email" type="email" class="form-control-modern w-100" required>
                                    <div v-if="errors.email" class="error-feedback">{{ errors.email[0] }}</div>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="form-label">Phone Number</label>
                                    <input v-model="form.phone" type="text" class="form-control-modern w-100" placeholder="+1 (555) 000-0000">
                                    <div v-if="errors.phone" class="error-feedback">{{ errors.phone[0] }}</div>
                                </div>
                            </div>

                            <!-- Tab: Security -->
                            <div v-if="activeTab === 'security'" class="tab-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">New Password</label>
                                        <input v-model="form.password" type="password" class="form-control-modern w-100" placeholder="Min. 8 characters">
                                        <div v-if="errors.password" class="error-feedback">{{ errors.password[0] }}</div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Confirm New Password</label>
                                        <input v-model="form.password_confirmation" type="password" class="form-control-modern w-100" placeholder="Repeat password">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gradient px-4" style="height: 44px; border-radius: 8px;" :disabled="isSubmitting">
                                <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin mr-1"></i> Saving changes...</span>
                                <span v-else>Update Profile</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>