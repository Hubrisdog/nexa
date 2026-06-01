<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const activeTab = ref('general');

const form = ref({
    app_name: '',
    business_email: '',
    business_hours: '',
    booking_interval: '30 mins',
    enable_notifications: 'true'
});

const isNotificationsEnabled = ref(true);

// Sync toggle state with form value
watch(isNotificationsEnabled, (newVal) => {
    form.value.enable_notifications = newVal ? 'true' : 'false';
});

const isSuccess = ref(false);
const isSubmitting = ref(false);
const errors = ref({});

const tenant = ref(null);
const isUpgrading = ref(false);

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/settings');
        if (response.data) {
            form.value.app_name = response.data.app_name || '';
            form.value.business_email = response.data.business_email || '';
            form.value.business_hours = response.data.business_hours || '';
            form.value.booking_interval = response.data.booking_interval || '30 mins';
            form.value.enable_notifications = response.data.enable_notifications || 'true';
            
            isNotificationsEnabled.value = form.value.enable_notifications === 'true';
        }
    } catch (error) {
        console.error("Error fetching settings:", error);
    }
};

const fetchBilling = async () => {
    try {
        const response = await axios.get('/api/billing/details');
        tenant.value = response.data;
    } catch (error) {
        console.error("Error fetching billing details:", error);
    }
};

const handleUpgradePlan = async (targetPlan) => {
    isUpgrading.value = true;
    try {
        const response = await axios.post('/api/billing/subscribe', { plan: targetPlan });
        tenant.value = response.data;
    } catch (error) {
        console.error("Error upgrading plan:", error);
    } finally {
        isUpgrading.value = false;
    }
};

const handleSave = async () => {
    isSubmitting.value = true;
    isSuccess.value = false;
    errors.value = {};
    try {
        await axios.post('/api/settings', form.value);
        isSuccess.value = true;
        setTimeout(() => {
            isSuccess.value = false;
        }, 3000);
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error("Error saving settings:", error);
        }
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(() => {
    fetchSettings();
    fetchBilling();
});
</script>

<template>
    <div class="content-header py-4">
        <div class="container-fluid">
            <h1 class="font-weight-extrabold mb-1 tracking-tight" style="font-size: 26px;">Settings</h1>
            <p class="text-muted mb-0 text-sm">Configure system details, clinic scheduling parameters, and alert integrations.</p>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-9">
                    <div class="card glass-card border-0 p-4">
                        <!-- Custom Premium Tabs -->
                        <div class="nav-tabs-custom">
                            <span @click="activeTab = 'general'" class="tab-link" :class="{ 'active': activeTab === 'general' }">
                                <i class="fas fa-sliders-h mr-2"></i> General Details
                            </span>
                            <span @click="activeTab = 'hours'" class="tab-link" :class="{ 'active': activeTab === 'hours' }">
                                <i class="far fa-clock mr-2"></i> Working Hours
                            </span>
                            <span @click="activeTab = 'notifications'" class="tab-link" :class="{ 'active': activeTab === 'notifications' }">
                                <i class="far fa-bell mr-2"></i> Email alerts
                            </span>
                            <span @click="activeTab = 'billing'" class="tab-link" :class="{ 'active': activeTab === 'billing' }">
                                <i class="fas fa-credit-card mr-2"></i> Billing & Plan
                            </span>
                        </div>

                        <div v-if="isSuccess" class="alert alert-success mb-4" style="border-radius: 8px;">
                            <i class="fas fa-check-circle mr-2"></i> System configurations saved successfully.
                        </div>

                        <form @submit.prevent="handleSave">
                            <!-- Tab: General -->
                            <div v-if="activeTab === 'general'" class="tab-body">
                                <div class="form-group mb-3">
                                    <label class="form-label">Application Branding Name</label>
                                    <input v-model="form.app_name" type="text" class="form-control-modern w-100" placeholder="Nexa Console" required>
                                    <span class="input-label-subtitle">Sets the main header branding title displayed throughout the app interface.</span>
                                    <div v-if="errors.app_name" class="error-feedback">{{ errors.app_name[0] }}</div>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="form-label">Support Email Address</label>
                                    <input v-model="form.business_email" type="email" class="form-control-modern w-100" placeholder="support@nexasched.com" required>
                                    <span class="input-label-subtitle">Used as default sender email address for all outgoing notification messages.</span>
                                    <div v-if="errors.business_email" class="error-feedback">{{ errors.business_email[0] }}</div>
                                </div>
                            </div>

                            <!-- Tab: Working Hours -->
                            <div v-if="activeTab === 'hours'" class="tab-body">
                                <div class="form-group mb-3">
                                    <label class="form-label">Business Hours Range</label>
                                    <input v-model="form.business_hours" type="text" class="form-control-modern w-100" placeholder="09:00 - 18:00" required>
                                    <span class="input-label-subtitle">Standard office hours window during which calendar slots can be booked.</span>
                                    <div v-if="errors.business_hours" class="error-feedback">{{ errors.business_hours[0] }}</div>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="form-label">Appointment Slot Duration</label>
                                    <select v-model="form.booking_interval" class="form-control-modern w-100" required>
                                        <option value="15 mins">15 Minutes</option>
                                        <option value="30 mins">30 Minutes</option>
                                        <option value="45 mins">45 Minutes</option>
                                        <option value="1 hour">1 Hour</option>
                                    </select>
                                    <span class="input-label-subtitle">Default scheduling slot increments used when creating bookings.</span>
                                    <div v-if="errors.booking_interval" class="error-feedback">{{ errors.booking_interval[0] }}</div>
                                </div>
                            </div>

                            <!-- Tab: Email alerts -->
                            <div v-if="activeTab === 'notifications'" class="tab-body">
                                <div class="form-group mb-4">
                                    <label class="form-label mb-2">Automated Notifications</label>
                                    <div class="switch-container">
                                        <div>
                                            <span class="font-weight-bold d-block text-sm" style="color: #0f172a;">Booking Confirmations</span>
                                            <span class="text-muted small">Send automatic email notifications to clients when scheduling updates occur.</span>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" v-model="isNotificationsEnabled">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab: Billing -->
                            <div v-if="activeTab === 'billing'" class="tab-body">
                                <div v-if="tenant" class="mb-4">
                                    <div class="p-3 bg-light rounded-lg mb-4 border border-light d-flex justify-content-between align-items-center" style="border-radius: 12px;">
                                        <div>
                                            <span class="text-xs uppercase text-muted font-weight-bold block">Current Organization Plan</span>
                                            <span class="font-weight-extrabold text-indigo uppercase" style="font-size: 18px; color: #4f46e5; font-family: 'Inter', sans-serif;">{{ tenant.plan }} Plan</span>
                                        </div>
                                        <span class="badge badge-success px-3 py-1.5 rounded-pill" style="font-size: 11px; border-radius: 12px; background-color: #10b981; color: white;">{{ tenant.subscription_status?.toUpperCase() || 'ACTIVE' }}</span>
                                    </div>

                                    <h6 class="font-weight-bold text-slate-700 mb-3" style="color: #334155; font-size: 14px;">Upgrade or Manage Subscriptions</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card p-3 text-center border rounded-lg h-100 d-flex flex-column justify-content-between" :style="tenant.plan === 'free' ? 'border-color: #4f46e5 !important; box-shadow: 0 4px 6px rgba(79,70,229,0.08);' : ''" style="border-radius: 12px;">
                                                <div>
                                                    <span class="font-weight-bold d-block text-slate-800" style="font-size: 15px; color: #0f172a;">Starter Free</span>
                                                    <span class="text-muted text-xs d-block mt-1">For single users</span>
                                                    <h4 class="font-weight-extrabold my-3" style="color: #0f172a; font-size: 24px;">$0<span class="text-xs text-muted font-weight-normal" style="font-size: 12px;">/mo</span></h4>
                                                    <ul class="list-unstyled text-xs text-muted text-left mb-4" style="font-size: 12px; padding-left: 0; list-style: none;">
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> 1 Provider Sync</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> Basic Reminders</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> Nexa Booking Page</li>
                                                    </ul>
                                                </div>
                                                <button type="button" @click="handleUpgradePlan('free')" class="btn btn-sm btn-outline-indigo w-100" :disabled="tenant.plan === 'free' || isUpgrading" style="border-radius: 8px; font-weight: 500; font-size: 12px;">
                                                    {{ tenant.plan === 'free' ? 'Current Plan' : 'Downgrade' }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <div class="card p-3 text-center border rounded-lg h-100 d-flex flex-column justify-content-between" :style="tenant.plan === 'pro' ? 'border-color: #4f46e5 !important; box-shadow: 0 4px 6px rgba(79,70,229,0.08);' : ''" style="border-radius: 12px;">
                                                <div>
                                                    <span class="font-weight-bold d-block text-slate-800" style="font-size: 15px; color: #0f172a;">Professional Pro</span>
                                                    <span class="text-muted text-xs d-block mt-1">For scaling clinics</span>
                                                    <h4 class="font-weight-extrabold my-3" style="color: #0f172a; font-size: 24px;">$49<span class="text-xs text-muted font-weight-normal" style="font-size: 12px;">/mo</span></h4>
                                                    <ul class="list-unstyled text-xs text-muted text-left mb-4" style="font-size: 12px; padding-left: 0; list-style: none;">
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> Unlimited Calendars</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> Automated Sequences</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> Teams/Zoom Links</li>
                                                    </ul>
                                                </div>
                                                <button type="button" @click="handleUpgradePlan('pro')" class="btn btn-sm btn-indigo w-100" :disabled="tenant.plan === 'pro' || isUpgrading" style="border-radius: 8px; font-weight: 500; font-size: 12px; background-color:#4f46e5; color:white; border:0;">
                                                    {{ tenant.plan === 'pro' ? 'Current Plan' : 'Upgrade to Pro' }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <div class="card p-3 text-center border rounded-lg h-100 d-flex flex-column justify-content-between" :style="tenant.plan === 'enterprise' ? 'border-color: #4f46e5 !important; box-shadow: 0 4px 6px rgba(79,70,229,0.08);' : ''" style="border-radius: 12px;">
                                                <div>
                                                    <span class="font-weight-bold d-block text-slate-800" style="font-size: 15px; color: #0f172a;">Enterprise SaaS</span>
                                                    <span class="text-muted text-xs d-block mt-1">Multi-office scales</span>
                                                    <h4 class="font-weight-extrabold my-3" style="color: #0f172a; font-size: 24px;">$149<span class="text-xs text-muted font-weight-normal" style="font-size: 12px;">/mo</span></h4>
                                                    <ul class="list-unstyled text-xs text-muted text-left mb-4" style="font-size: 12px; padding-left: 0; list-style: none;">
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> Multi-Tenant Scopes</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> Custom WhatsApp APIs</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:#4f46e5;"></i> 24/7 Priority Support</li>
                                                    </ul>
                                                </div>
                                                <button type="button" @click="handleUpgradePlan('enterprise')" class="btn btn-sm btn-indigo w-100" :disabled="tenant.plan === 'enterprise' || isUpgrading" style="border-radius: 8px; font-weight: 500; font-size: 12px; background-color:#4f46e5; color:white; border:0;">
                                                    {{ tenant.plan === 'enterprise' ? 'Current Plan' : 'Upgrade to Enterprise' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button v-if="activeTab !== 'billing'" type="submit" class="btn btn-gradient px-4" style="height: 44px; border-radius: 8px;" :disabled="isSubmitting">
                                <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                                <span v-else>Save Settings</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>