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

// Calendar integrations
const calendarConnections = ref([]);

// Branding settings
const brandingForm = ref({
    name: '',
    brand_color: '#4f46e5',
    custom_domain: '',
    custom_email_footer: '',
    logo: null
});
const logoPreview = ref(null);
const isSavingBranding = ref(false);
const isBrandingSuccess = ref(false);

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
        if (tenant.value) {
            brandingForm.value.name = tenant.value.name || '';
            brandingForm.value.brand_color = tenant.value.brand_color || '#4f46e5';
            brandingForm.value.custom_domain = tenant.value.custom_domain || '';
            brandingForm.value.custom_email_footer = tenant.value.custom_email_footer || '';
            if (tenant.value.logo_path) {
                logoPreview.value = '/' + tenant.value.logo_path;
            }
        }
    } catch (error) {
        console.error("Error fetching billing details:", error);
    }
};

const fetchConnections = async () => {
    try {
        const response = await axios.get('/api/oauth/connections');
        calendarConnections.value = response.data;
    } catch (error) {
        console.error("Error fetching calendar connections:", error);
    }
};

const disconnectCalendar = async (provider) => {
    try {
        await axios.delete(`/api/oauth/connections/${provider}`);
        fetchConnections();
    } catch (error) {
        console.error("Error disconnecting calendar connection:", error);
    }
};

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        brandingForm.value.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const saveBranding = async () => {
    isSavingBranding.value = true;
    isBrandingSuccess.value = false;
    try {
        const formData = new FormData();
        formData.append('name', brandingForm.value.name);
        formData.append('brand_color', brandingForm.value.brand_color);
        formData.append('custom_domain', brandingForm.value.custom_domain || '');
        formData.append('custom_email_footer', brandingForm.value.custom_email_footer || '');
        if (brandingForm.value.logo) {
            formData.append('logo', brandingForm.value.logo);
        }

        const response = await axios.post('/api/settings/branding', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        tenant.value = response.data.tenant;
        isBrandingSuccess.value = true;
        
        // Refresh general app branding
        fetchSettings();
        
        setTimeout(() => {
            isBrandingSuccess.value = false;
        }, 3000);
    } catch (error) {
        console.error("Error saving branding settings:", error);
        alert(error.response?.data?.message || 'Error saving branding settings.');
    } finally {
        isSavingBranding.value = false;
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
    fetchConnections();

    // Check URL parameters for successful OAuth sync redirection
    const params = new URLSearchParams(window.location.search);
    if (params.get('sync') === 'success') {
        activeTab.value = 'calendar';
        isSuccess.value = true;
        setTimeout(() => { isSuccess.value = false; }, 3000);
    }
});

// Webhooks settings state
const webhookSubscriptions = ref([]);
const webhookDeliveries = ref([]);
const isSavingWebhook = ref(false);
const isTestingWebhook = ref({});
const newWebhook = ref({
    url: '',
    secret: '',
    events: ['appointment.created'],
    is_active: true
});
const editingWebhookId = ref(null);

const fetchWebhooks = async () => {
    try {
        const response = await axios.get('/api/webhooks');
        webhookSubscriptions.value = response.data.subscriptions;
        webhookDeliveries.value = response.data.deliveries;
    } catch (error) {
        console.error("Error fetching webhooks:", error);
    }
};

const saveWebhook = async () => {
    isSavingWebhook.value = true;
    try {
        if (editingWebhookId.value) {
            await axios.put(`/api/webhooks/${editingWebhookId.value}`, newWebhook.value);
        } else {
            await axios.post('/api/webhooks', newWebhook.value);
        }
        newWebhook.value = {
            url: '',
            secret: '',
            events: ['appointment.created'],
            is_active: true
        };
        editingWebhookId.value = null;
        await fetchWebhooks();
    } catch (error) {
        console.error("Error saving webhook:", error);
        alert(error.response?.data?.message || 'Error saving webhook subscription.');
    } finally {
        isSavingWebhook.value = false;
    }
};

const editWebhook = (sub) => {
    editingWebhookId.value = sub.id;
    newWebhook.value = {
        url: sub.url,
        secret: sub.secret,
        events: Array.isArray(sub.events) ? sub.events : ['appointment.created'],
        is_active: !!sub.is_active
    };
};

const deleteWebhook = async (id) => {
    if (confirm('Are you sure you want to delete this webhook subscription?')) {
        try {
            await axios.delete(`/api/webhooks/${id}`);
            await fetchWebhooks();
        } catch (error) {
            console.error("Error deleting webhook:", error);
        }
    }
};

const testWebhook = async (id) => {
    isTestingWebhook.value[id] = true;
    try {
        await axios.post(`/api/webhooks/${id}/test`);
        alert('Test webhook payload queued successfully!');
        setTimeout(() => {
            fetchWebhooks();
        }, 1500);
    } catch (error) {
        console.error("Error testing webhook:", error);
        alert('Failed to trigger test webhook.');
    } finally {
        isTestingWebhook.value[id] = false;
    }
};

// SSL Provisioning state (Mock)
const sslStatus = ref('unprovisioned'); // unprovisioned, generating, active
const sslGeneratingStep = ref(0);
const isProvisioningSsl = ref(false);

const startSslProvisioning = async () => {
    if (isProvisioningSsl.value) return;
    isProvisioningSsl.value = true;
    sslStatus.value = 'generating';
    sslGeneratingStep.value = 1;

    await new Promise(resolve => setTimeout(resolve, 1500));
    sslGeneratingStep.value = 2;
    await new Promise(resolve => setTimeout(resolve, 1500));
    sslGeneratingStep.value = 3;
    await new Promise(resolve => setTimeout(resolve, 1500));
    sslGeneratingStep.value = 4;
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    sslStatus.value = 'active';
    isProvisioningSsl.value = false;
    alert('SSL certificate successfully provisioned and installed!');
};

onMounted(() => {
    fetchWebhooks();
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
                            <span @click="activeTab = 'calendar'" class="tab-link" :class="{ 'active': activeTab === 'calendar' }">
                                <i class="far fa-calendar-alt mr-2"></i> Calendar Sync
                            </span>
                            <span @click="activeTab = 'branding'" class="tab-link" :class="{ 'active': activeTab === 'branding' }">
                                <i class="fas fa-paint-brush mr-2"></i> Branding & Logo
                            </span>
                            <span @click="activeTab = 'webhooks'" class="tab-link" :class="{ 'active': activeTab === 'webhooks' }">
                                <i class="fas fa-code mr-2"></i> Webhooks
                            </span>
                            <span @click="activeTab = 'ssl'" class="tab-link" :class="{ 'active': activeTab === 'ssl' }">
                                <i class="fas fa-shield-alt mr-2"></i> Domains & SSL
                            </span>
                            <span @click="activeTab = 'billing'" class="tab-link" :class="{ 'active': activeTab === 'billing' }">
                                <i class="fas fa-credit-card mr-2"></i> Billing & Plan
                            </span>
                        </div>

                        <div v-if="isSuccess" class="alert alert-success mb-4" style="border-radius: 8px;">
                            <i class="fas fa-check-circle mr-2"></i> Settings saved successfully.
                        </div>

                        <!-- Tab Content Views -->
                        <form @submit.prevent="handleSave" v-if="activeTab !== 'calendar' && activeTab !== 'branding'">
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
                                            <span class="font-weight-bold d-block text-sm" style="color: var(--text-primary);">Booking Confirmations</span>
                                            <span class="text-muted small">Send automatic email notifications to clients when scheduling updates occur.</span>
                                        </div>
                                        <label class="switch">
                                            <input type="checkbox" v-model="isNotificationsEnabled">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeTab === 'billing'" class="tab-body">
                                <div v-if="tenant" class="mb-4">
                                    <div class="p-3 rounded-lg mb-4 border d-flex justify-content-between align-items-center" style="border-radius: 12px; background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important;">
                                        <div>
                                            <span class="text-xs uppercase text-muted font-weight-bold block">Current Organization Plan</span>
                                            <span class="font-weight-extrabold text-indigo uppercase" style="font-size: 18px; color: var(--primary-color); font-family: 'Inter', sans-serif;">{{ tenant.plan }} Plan</span>
                                        </div>
                                        <span class="badge badge-success px-3 py-1.5 rounded-pill" style="font-size: 11px; border-radius: 12px; background-color: #10b981; color: white;">{{ tenant.subscription_status?.toUpperCase() || 'ACTIVE' }}</span>
                                    </div>

                                    <h6 class="font-weight-bold mb-3" style="color: var(--text-secondary); font-size: 14px;">Upgrade or Manage Subscriptions</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card p-3 text-center border rounded-lg h-100 d-flex flex-column justify-content-between" :style="tenant.plan === 'free' ? 'border-color: var(--primary-color) !important; box-shadow: 0 4px 6px rgba(99,102,241,0.08);' : ''" style="border-radius: 12px;">
                                                <div>
                                                    <span class="font-weight-bold d-block" style="font-size: 15px; color: var(--text-primary);">Starter Free</span>
                                                    <span class="text-muted text-xs d-block mt-1">For single users</span>
                                                    <h4 class="font-weight-extrabold my-3" style="color: var(--text-primary); font-size: 24px;">$0<span class="text-xs text-muted font-weight-normal" style="font-size: 12px;">/mo</span></h4>
                                                    <ul class="list-unstyled text-xs text-muted text-left mb-4" style="font-size: 12px; padding-left: 0; list-style: none;">
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> 1 Provider Sync</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> Basic Reminders</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> Nexa Booking Page</li>
                                                    </ul>
                                                </div>
                                                <button type="button" @click="handleUpgradePlan('free')" class="btn btn-sm btn-outline-indigo w-100" :disabled="tenant.plan === 'free' || isUpgrading" style="border-radius: 8px; font-weight: 500; font-size: 12px;">
                                                    {{ tenant.plan === 'free' ? 'Current Plan' : 'Downgrade' }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <div class="card p-3 text-center border rounded-lg h-100 d-flex flex-column justify-content-between" :style="tenant.plan === 'pro' ? 'border-color: var(--primary-color) !important; box-shadow: 0 4px 6px rgba(99,102,241,0.08);' : ''" style="border-radius: 12px;">
                                                <div>
                                                    <span class="font-weight-bold d-block" style="font-size: 15px; color: var(--text-primary);">Professional Pro</span>
                                                    <span class="text-muted text-xs d-block mt-1">For scaling clinics</span>
                                                    <h4 class="font-weight-extrabold my-3" style="color: var(--text-primary); font-size: 24px;">$49<span class="text-xs text-muted font-weight-normal" style="font-size: 12px;">/mo</span></h4>
                                                    <ul class="list-unstyled text-xs text-muted text-left mb-4" style="font-size: 12px; padding-left: 0; list-style: none;">
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> Unlimited Calendars</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> Automated Sequences</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> Teams/Zoom Links</li>
                                                    </ul>
                                                </div>
                                                <button type="button" @click="handleUpgradePlan('pro')" class="btn btn-sm btn-indigo w-100" :disabled="tenant.plan === 'pro' || isUpgrading" style="border-radius: 8px; font-weight: 500; font-size: 12px; background: var(--primary-gradient); color:white; border:0;">
                                                    {{ tenant.plan === 'pro' ? 'Current Plan' : 'Upgrade to Pro' }}
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <div class="card p-3 text-center border rounded-lg h-100 d-flex flex-column justify-content-between" :style="tenant.plan === 'enterprise' ? 'border-color: var(--primary-color) !important; box-shadow: 0 4px 6px rgba(99,102,241,0.08);' : ''" style="border-radius: 12px;">
                                                <div>
                                                    <span class="font-weight-bold d-block" style="font-size: 15px; color: var(--text-primary);">Enterprise SaaS</span>
                                                    <span class="text-muted text-xs d-block mt-1">Multi-office scales</span>
                                                    <h4 class="font-weight-extrabold my-3" style="color: var(--text-primary); font-size: 24px;">$149<span class="text-xs text-muted font-weight-normal" style="font-size: 12px;">/mo</span></h4>
                                                    <ul class="list-unstyled text-xs text-muted text-left mb-4" style="font-size: 12px; padding-left: 0; list-style: none;">
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> Multi-Tenant Scopes</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> Custom WhatsApp APIs</li>
                                                        <li class="mb-1"><i class="fas fa-check text-indigo mr-1" style="color:var(--primary-color);"></i> 24/7 Priority Support</li>
                                                    </ul>
                                                </div>
                                                <button type="button" @click="handleUpgradePlan('enterprise')" class="btn btn-sm btn-indigo w-100" :disabled="tenant.plan === 'enterprise' || isUpgrading" style="border-radius: 8px; font-weight: 500; font-size: 12px; background: var(--primary-gradient); color:white; border:0;">
                                                    {{ tenant.plan === 'enterprise' ? 'Current Plan' : 'Upgrade to Enterprise' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gradient px-4" style="height: 44px; border-radius: 8px;" :disabled="isSubmitting">
                                <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                                <span v-else>Save Settings</span>
                            </button>
                        </form>

                        <!-- Tab: Calendar Sync -->
                        <div v-if="activeTab === 'calendar'" class="tab-body py-2">
                            <h6 class="font-weight-bold text-white mb-2" style="font-size: 15px;">Calendar Sync Connections</h6>
                            <p class="text-secondary text-sm mb-4">Connect external accounts to automatically block busy slots and push synced invitations.</p>
                            
                            <div class="d-flex flex-column gap-3 mb-4">
                                <!-- Google Calendar Row -->
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-lg border" style="background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important; border-radius: 12px;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center text-blue" style="width: 44px; height: 44px; border-radius: 10px; font-size: 20px; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                            <i class="fab fa-google"></i>
                                        </div>
                                        <div>
                                            <span class="font-weight-bold d-block text-white text-sm">Google Calendar Sync</span>
                                            <span v-if="calendarConnections.find(c => c.provider === 'google')" class="text-xs text-secondary d-block mt-0.5">
                                                Connected as <span class="text-success font-weight-bold">{{ calendarConnections.find(c => c.provider === 'google').email }}</span>
                                            </span>
                                            <span v-else class="text-xs text-muted d-block mt-0.5">Not connected</span>
                                        </div>
                                    </div>
                                    <div>
                                        <button v-if="calendarConnections.find(c => c.provider === 'google')" type="button" @click="disconnectCalendar('google')" class="btn btn-sm btn-outline-danger px-3 py-1.5" style="border-radius: 8px; font-size: 12px; font-weight: 500;">
                                            Disconnect
                                        </button>
                                        <a v-else href="/api/oauth/google/redirect" class="btn btn-sm btn-indigo px-3 py-1.5 text-white" style="border-radius: 8px; font-size: 12px; font-weight: 500; background-color: #4f46e5; border: 0; text-decoration: none;">
                                            Connect Calendar
                                        </a>
                                    </div>
                                </div>

                                <!-- Outlook Calendar Row -->
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-lg border opacity-50" style="background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important; border-radius: 12px;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="d-flex align-items-center justify-content-center text-orange" style="width: 44px; height: 44px; border-radius: 10px; font-size: 20px; background-color: rgba(249, 115, 22, 0.1); color: #f97316;">
                                            <i class="fab fa-windows"></i>
                                        </div>
                                        <div>
                                            <span class="font-weight-bold d-block text-white text-sm">Microsoft Outlook Calendar</span>
                                            <span class="text-xs text-muted d-block mt-0.5">Available in Phase 3</span>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-dark disabled px-3 py-1.5" style="border-radius: 8px; font-size: 12px;" disabled>
                                            Coming Soon
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Branding & Logo -->
                        <div v-if="activeTab === 'branding'" class="tab-body py-2">
                            <div v-if="isBrandingSuccess" class="alert alert-success mb-4" style="border-radius: 8px;">
                                <i class="fas fa-check-circle mr-2"></i> Branding settings updated successfully.
                            </div>

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Workspace Business Name</label>
                                        <input v-model="brandingForm.name" type="text" class="form-control-modern w-100" placeholder="Acme Logistics V2" required>
                                        <span class="input-label-subtitle">Sets the main white-labeled client-facing brand name.</span>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label">Brand Highlight Color</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <input v-model="brandingForm.brand_color" type="color" class="form-control-color" style="width: 44px; height: 44px; border: 0; padding: 0; background: none; cursor: pointer; border-radius: 8px;">
                                            <input v-model="brandingForm.brand_color" type="text" class="form-control-modern w-50" placeholder="#4f46e5" required>
                                        </div>
                                        <span class="input-label-subtitle">Theme color used for client-facing booking wizard highlights.</span>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="form-label">Custom White-Label Domain</label>
                                        <input v-model="brandingForm.custom_domain" type="text" class="form-control-modern w-100" placeholder="book.acme.com">
                                        <span class="input-label-subtitle">CNAME record maps this address to resolve client visits directly.</span>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label class="form-label">Email Signature Footer</label>
                                        <textarea v-model="brandingForm.custom_email_footer" class="form-control-modern w-100" rows="3" placeholder="Thanks, Acme Automation team."></textarea>
                                        <span class="input-label-subtitle">Custom sign-off footer text attached to all automated invitations.</span>
                                    </div>
                                </div>

                                <div class="col-md-5 d-flex flex-column align-items-center justify-content-center p-3 border-left" style="border-color: var(--border-dark) !important;">
                                    <label class="form-label mb-3 text-center">Company Identity Logo</label>
                                    <div class="logo-preview-box mb-3 border border-dashed d-flex align-items-center justify-content-center overflow-hidden" style="width: 140px; height: 140px; border-radius: 20px; border-color: var(--border-dark) !important; background-color: var(--bg-dark-hover);">
                                        <img v-if="logoPreview" :src="logoPreview" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                                        <i v-else class="fas fa-image fa-3x text-secondary"></i>
                                    </div>
                                    <label class="btn btn-sm btn-dark border-secondary px-3 py-2" style="border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 500;">
                                        <i class="fas fa-cloud-upload-alt mr-1"></i> Upload Image
                                        <input type="file" @change="handleLogoUpload" accept="image/*" class="d-none">
                                    </label>
                                    <span class="text-xs text-muted mt-2 text-center">Max size: 2MB. Accepts PNG, JPG.</span>
                                </div>
                            </div>

                            <button type="button" @click="saveBranding" class="btn btn-gradient px-4 mt-3" style="height: 44px; border-radius: 8px;" :disabled="isSavingBranding">
                                <span v-if="isSavingBranding"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                                <span v-else>Save Branding Details</span>
                            </button>
                        </div>

                        <!-- Tab: Webhooks -->
                        <div v-if="activeTab === 'webhooks'" class="tab-body py-2">
                            <h6 class="font-weight-bold text-white mb-2" style="font-size: 15px;">Developer Outbound Webhooks</h6>
                            <p class="text-secondary text-sm mb-4">Register webhooks to receive real-time JSON payloads when scheduling events occur in your tenant.</p>
                            
                            <!-- Form to Add/Edit Webhook -->
                            <div class="card p-4 mb-4 border" style="border-radius: 12px; background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important;">
                                <h6 class="font-weight-bold text-white mb-3" style="font-size: 14px;">
                                    {{ editingWebhookId ? 'Edit Webhook Subscription' : 'Register New Webhook Endpoint' }}
                                </h6>
                                <form @submit.prevent="saveWebhook">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-xs">Target Endpoint URL</label>
                                            <input v-model="newWebhook.url" type="url" class="form-control-modern w-100" placeholder="https://api.yourdomain.com/webhooks/nexa" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-xs">Secret Signing Key (HMAC SHA256)</label>
                                            <input v-model="newWebhook.secret" type="text" class="form-control-modern w-100" placeholder="whsec_xxxxxxxxxxxxxxxxxxxxxx" required>
                                        </div>
                                    </div>
                                    <div class="row align-items-center">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-xs d-block mb-2">Subscribed Events</label>
                                            <div class="d-flex gap-3 flex-wrap">
                                                <label class="d-flex align-items-center gap-2 text-sm text-secondary cursor-pointer">
                                                    <input type="checkbox" value="appointment.created" v-model="newWebhook.events">
                                                    <span>appointment.created</span>
                                                </label>
                                                <label class="d-flex align-items-center gap-2 text-sm text-secondary cursor-pointer">
                                                    <input type="checkbox" value="deal.updated" v-model="newWebhook.events">
                                                    <span>deal.updated</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3 text-right pt-3">
                                            <button v-if="editingWebhookId" type="button" @click="editingWebhookId = null; newWebhook = {url:'', secret:'', events:['appointment.created'], is_active:true}" class="btn btn-sm btn-outline-secondary mr-2" style="border-radius: 8px;">Cancel</button>
                                            <button type="submit" class="btn btn-sm btn-indigo px-4" style="border-radius: 8px; font-weight: 500;" :disabled="isSavingWebhook">
                                                {{ isSavingWebhook ? 'Saving...' : (editingWebhookId ? 'Update Subscription' : 'Register Webhook') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- List of Subscriptions -->
                            <div class="mb-4">
                                <h6 class="font-weight-bold text-white mb-3" style="font-size: 14px;">Configured Webhooks</h6>
                                <div v-if="webhookSubscriptions.length === 0" class="text-center py-4 text-muted border border-dashed rounded-lg" style="border-color: var(--border-dark) !important;">
                                    No active webhooks configured for this workspace.
                                </div>
                                <div v-else class="d-flex flex-column gap-3">
                                    <div v-for="sub in webhookSubscriptions" :key="sub.id" class="d-flex align-items-center justify-content-between p-3 rounded-lg border" style="background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important; border-radius: 12px;">
                                        <div class="text-left" style="max-width: 70%;">
                                            <span class="font-weight-bold d-block text-white text-sm truncate">{{ sub.url }}</span>
                                            <div class="d-flex gap-2 mt-1.5 flex-wrap">
                                                <span v-for="ev in sub.events" :key="ev" class="badge bg-indigo text-xs text-white" style="background: var(--primary-color);">{{ ev }}</span>
                                                <span class="badge text-xs" :class="sub.is_active ? 'badge-success' : 'badge-secondary'">{{ sub.is_active ? 'Active' : 'Paused' }}</span>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button @click="testWebhook(sub.id)" class="btn btn-xs btn-outline-indigo" style="border-radius: 6px; font-weight: 500;" :disabled="isTestingWebhook[sub.id]">
                                                <i class="fas mr-1" :class="isTestingWebhook[sub.id] ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                                                <span>{{ isTestingWebhook[sub.id] ? 'Testing...' : 'Test Payload' }}</span>
                                            </button>
                                            <button @click="editWebhook(sub)" class="btn btn-xs btn-outline-secondary" style="border-radius: 6px;"><i class="fas fa-edit"></i></button>
                                            <button @click="deleteWebhook(sub.id)" class="btn btn-xs btn-outline-danger" style="border-radius: 6px;"><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Deliveries Logs Card -->
                            <div>
                                <h6 class="font-weight-bold text-white mb-3" style="font-size: 14px;">Recent Webhook Deliveries Log</h6>
                                <div class="card border" style="border-radius: 12px; background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important; overflow: hidden;">
                                    <div class="table-responsive" style="max-height: 250px;">
                                        <table class="table table-dark table-striped mb-0 text-sm">
                                            <thead>
                                                <tr>
                                                    <th>Event</th>
                                                    <th>Status</th>
                                                    <th>Target URL</th>
                                                    <th>Latency</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-if="webhookDeliveries.length === 0">
                                                    <td colspan="5" class="text-center py-3 text-muted">No webhook deliveries logged yet.</td>
                                                </tr>
                                                <tr v-for="log in webhookDeliveries" :key="log.id">
                                                    <td><code>{{ log.event }}</code></td>
                                                    <td>
                                                        <span class="badge" :class="log.response_status >= 200 && log.response_status < 300 ? 'badge-success' : 'badge-danger'">
                                                            {{ log.response_status || 'Failed' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-truncate" style="max-width: 200px;">{{ log.subscription?.url || 'Test URL' }}</td>
                                                    <td>{{ log.duration_ms }}ms</td>
                                                    <td>{{ new Date(log.created_at).toLocaleTimeString() }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: SSL Domains -->
                        <div v-if="activeTab === 'ssl'" class="tab-body py-2">
                            <h6 class="font-weight-bold text-white mb-2" style="font-size: 15px;">Custom Domain SSL Lifecycle Manager</h6>
                            <p class="text-secondary text-sm mb-4">Route B2B scheduling interactions through your own brand. Point your DNS records and provision a dedicated Let's Encrypt certificate.</p>
                            
                            <div class="card p-4 border mb-4" style="border-radius: 12px; background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important;">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                                    <div>
                                        <span class="text-muted text-xs font-weight-bold uppercase tracking-wider block">Custom Domain Address</span>
                                        <h5 class="font-weight-extrabold text-white mt-1 mb-0">{{ brandingForm.custom_domain || 'No custom domain configured' }}</h5>
                                    </div>
                                    <div>
                                        <span class="badge px-3 py-1.5 rounded-pill uppercase tracking-wider" 
                                              :class="sslStatus === 'active' ? 'badge-success' : (sslStatus === 'generating' ? 'badge-warning' : 'badge-secondary')" 
                                              style="font-size: 11px; border-radius: 12px;">
                                            SSL: {{ sslStatus }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="!brandingForm.custom_domain" class="text-muted text-sm py-2 text-left">
                                    <i class="fas fa-info-circle text-info mr-2"></i> Set a custom white-label domain in the <strong>Branding & Logo</strong> tab first to manage certificates.
                                </div>

                                <div v-else>
                                    <!-- DNS Instructions -->
                                    <h6 class="font-weight-bold text-white text-xs uppercase mb-3 text-left">1. Add DNS Mapping Record</h6>
                                    <div class="p-3 mb-4 rounded border text-sm text-secondary bg-dark text-left" style="font-family: monospace; border-color: var(--border-dark) !important;">
                                        <div class="row">
                                            <div class="col-sm-3 font-weight-bold text-white">TYPE</div>
                                            <div class="col-sm-4 font-weight-bold text-white">HOST</div>
                                            <div class="col-sm-5 font-weight-bold text-white">VALUE (TARGET)</div>
                                        </div>
                                        <hr class="my-2" style="border-color: var(--border-dark);">
                                        <div class="row mt-1">
                                            <div class="col-sm-3 text-success">CNAME</div>
                                            <div class="col-sm-4">{{ brandingForm.custom_domain.split('.')[0] }}</div>
                                            <div class="col-sm-5">cname.nexasched.com</div>
                                        </div>
                                    </div>

                                    <!-- Provisioning Steps Widget -->
                                    <h6 class="font-weight-bold text-white text-xs uppercase mb-3 text-left">2. Provision SSL Certificate</h6>
                                    
                                    <!-- Dynamic Progress steps -->
                                    <div v-if="sslStatus === 'generating'" class="p-4 mb-4 rounded border text-left" style="background: rgba(245, 158, 11, 0.04); border-color: rgba(245, 158, 11, 0.25) !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <span class="text-sm font-weight-bold text-warning"><i class="fas fa-sync fa-spin mr-2"></i> Generating SSL Certificate...</span>
                                            <span class="text-xs text-muted">{{ Math.round((sslGeneratingStep / 4) * 100) }}% complete</span>
                                        </div>
                                        <div class="progress progress-sm mb-3" style="height: 6px; background-color: var(--bg-dark-accent);">
                                            <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" :style="{ width: (sslGeneratingStep / 4) * 100 + '%' }"></div>
                                        </div>
                                        <ul class="list-unstyled text-xs text-muted mb-0" style="padding-left: 0; list-style: none;">
                                            <li class="mb-2" :class="{ 'text-success font-weight-bold': sslGeneratingStep >= 1 }">
                                                <i class="fas mr-1.5" :class="sslGeneratingStep > 1 ? 'fa-check-circle text-success' : (sslGeneratingStep === 1 ? 'fa-circle-notch fa-spin text-warning' : 'fa-circle')"></i>
                                                Checking DNS CNAME validation record...
                                            </li>
                                            <li class="mb-2" :class="{ 'text-success font-weight-bold': sslGeneratingStep >= 2 }">
                                                <i class="fas mr-1.5" :class="sslGeneratingStep > 2 ? 'fa-check-circle text-success' : (sslGeneratingStep === 2 ? 'fa-circle-notch fa-spin text-warning' : 'fa-circle')"></i>
                                                Requesting authorization challenge from Let's Encrypt CA...
                                            </li>
                                            <li class="mb-2" :class="{ 'text-success font-weight-bold': sslGeneratingStep >= 3 }">
                                                <i class="fas mr-1.5" :class="sslGeneratingStep > 3 ? 'fa-check-circle text-success' : (sslGeneratingStep === 3 ? 'fa-circle-notch fa-spin text-warning' : 'fa-circle')"></i>
                                                Generating cryptographic signature keys & cert payload...
                                            </li>
                                            <li class="mb-0" :class="{ 'text-success font-weight-bold': sslGeneratingStep >= 4 }">
                                                <i class="fas mr-1.5" :class="sslGeneratingStep >= 4 ? 'fa-check-circle text-success' : 'fa-circle'"></i>
                                                Installing SSL files to edge load balancers...
                                            </li>
                                        </ul>
                                    </div>

                                    <div v-else-if="sslStatus === 'active'" class="p-4 mb-4 rounded border text-sm text-secondary bg-dark-accent text-left" style="border-color: rgba(16, 185, 129, 0.2) !important; background: rgba(16, 185, 129, 0.03);">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="fas fa-check-circle text-success fa-2x"></i>
                                            <div>
                                                <span class="font-weight-bold text-white d-block">SSL Certificate active and healthy</span>
                                                <span class="text-xs text-muted block mt-0.5">Expires in 89 days (Auto-renewals enabled)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="text-sm text-secondary mb-4 text-left">
                                        Once your DNS settings propagation is complete, click below to verify ownership records and activate SSL encryption.
                                    </div>

                                    <div class="text-left">
                                        <button type="button" @click="startSslProvisioning" class="btn btn-indigo px-4" style="border-radius: 8px;" :disabled="sslStatus === 'generating'">
                                            <i class="fas fa-shield-alt mr-2"></i> Verify DNS & Activate SSL
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gap-3 { gap: 12px; }
.form-control-color {
    border: 1px solid var(--border-dark);
    border-radius: 8px;
    height: 44px;
    width: 44px;
    background-color: var(--bg-dark-hover);
}
.opacity-50 {
    opacity: 0.5;
}
.border-left {
    border-left: 1px solid var(--border-dark) !important;
}
</style>