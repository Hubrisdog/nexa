<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import OnboardingWizard from './OnboardingWizard.vue';

const router = useRouter();
const showOnboarding = ref(false);

const stats = ref({
    total_appointments: 0,
    total_clients: 0,
    total_staff: 0,
    completed_count: 0,
    cancelled_count: 0,
    scheduled_count: 0,
    confirmed_count: 0,
    completed_rate: 0,
    recent_appointments: [],
    appointments_breakdown: [],
    today_count: 0,
    today_appointments: [],
    recent_activities: []
});

const isLoading = ref(true);
const currentUser = ref(null);
const activeTab = ref('today');

const greeting = computed(() => {
    const hours = new Date().getHours();
    if (hours < 12) return 'Good morning';
    if (hours < 18) return 'Good afternoon';
    return 'Good evening';
});

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/dashboard-stats');
        stats.value = response.data;
    } catch (error) {
        console.error("Error fetching stats:", error);
    } finally {
        isLoading.value = false;
    }
};

const isSimulating = ref(false);
const toastMessage = ref('');
const showToast = ref(false);

const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

const showStartDayModal = ref(false);
const currentStep = ref(1);
const isSimStepLoading = ref(false);
const simStepProgress = ref(0);
const simStepLogs = ref([]);
const simStepSummary = ref('');
const demoDeals = ref([]);
const simFinished = ref(false);

const getStepTitle = (step) => {
    switch (step) {
        case 1: return 'Stark Arc Reactor Integration Sync';
        case 2: return 'Wayne Tactical Gear Licensing Review';
        case 3: return 'Cyberdyne CPU Architecture Sync';
        default: return '';
    }
};

const getStepIcon = (step) => {
    switch (step) {
        case 1: return 'fas fa-video';
        case 2: return 'fas fa-shield-alt';
        case 3: return 'fas fa-microchip';
        default: return '';
    }
};

const getStepSub = (step) => {
    switch (step) {
        case 1: return 'Conduct Stark Meeting & Auto-Generate AI Notes';
        case 2: return 'Check-In Wayne Deal & Update Appointment Status';
        case 3: return 'Complete Cyberdyne Sync & Advance CRM Stage';
        default: return '';
    }
};

const getStepDesc = (step) => {
    switch (step) {
        case 1: return 'Simulates conducting the meeting, updating Google Calendar status to completed, auto-generating AI summaries and moving the deal to Closed Won.';
        case 2: return 'Updates the appointment status to confirmed, moves the Wayne Enterprises deal to proposal/interested stage, and triggers notification sync.';
        case 3: return 'Completes the final scheduled appointment for the day, updates the Cyberdyne deal stage, and logs the day-end review.';
        default: return '';
    }
};

const startDay = async () => {
    const isDemo = currentUser.value?.tenant?.is_demo || currentUser.value?.tenant_id === 999;
    if (!isDemo) {
        if (stats.value.today_appointments.length > 0) {
            const firstApp = stats.value.today_appointments[0];
            triggerToast(`Starting your day! Your first appointment is with ${firstApp.client?.name || 'Client'} at ${formatTime(firstApp.start_time)}.`);
        } else {
            triggerToast("No appointments scheduled for today! Use your shareable link to book new clients.");
        }
        return;
    }

    showStartDayModal.value = true;
    currentStep.value = 1;
    simStepProgress.value = 0;
    simStepLogs.value = [];
    simStepSummary.value = '';
    simFinished.value = false;

    try {
        const response = await axios.get('/api/crm/pipeline');
        demoDeals.value = response.data;
    } catch (err) {
        console.error("Failed to load demo deals:", err);
    }
};

const executeSimulationStep = () => {
    isSimStepLoading.value = true;
    simStepProgress.value = 0;
    simStepLogs.value = [];
    
    let progress = 0;
    const interval = setInterval(async () => {
        progress += 5;
        simStepProgress.value = progress;
        
        if (currentStep.value === 1) {
            if (progress === 10) simStepLogs.value.push('[10:00 AM] Connecting to secure Google Meet room: nexa-demo-stark...');
            if (progress === 30) simStepLogs.value.push('[10:05 AM] Connection established. Pepper Potts (CEO, Stark Industries) joined.');
            if (progress === 50) simStepLogs.value.push('[10:15 AM] Meeting in progress: Discussed arc reactor B2B pipeline integrations.');
            if (progress === 70) simStepLogs.value.push('[10:25 AM] Running Nexa AI Summarizer to parse transcripts and generate insights...');
            if (progress === 90) {
                simStepLogs.value.push('[10:28 AM] AI Meeting summary generated and synced to CRM timeline!');
                simStepSummary.value = 'Pepper Potts approved the integrations document. We discussed moving forward with the enterprise contract immediately.';
            }
        } else if (currentStep.value === 2) {
            if (progress === 10) simStepLogs.value.push('[1:00 PM] Initiating calendar sync check for: Wayne Tactical Gear Licensing Review...');
            if (progress === 40) simStepLogs.value.push('[1:10 PM] Checking with staff member regarding availability update.');
            if (progress === 75) simStepLogs.value.push('[1:20 PM] Client Lucius Fox requested proposal draft; updating deal stage to Interested.');
            if (progress === 90) simStepLogs.value.push('[1:25 PM] Synced Outlook/Google Calendar invitation with updated Confirmed status.');
        } else if (currentStep.value === 3) {
            if (progress === 10) simStepLogs.value.push('[3:30 PM] Connecting to Cyberdyne CPU Architecture Sync...');
            if (progress === 35) simStepLogs.value.push('[3:40 PM] Reviewing B2B CRM pipeline stages with Lead Architect Miles Dyson.');
            if (progress === 60) simStepLogs.value.push('[3:50 PM] Syncing final appointment status to Completed.');
            if (progress === 85) simStepLogs.value.push('[4:00 PM] Logging final meeting notes in database. Closing daily analytics funnels...');
        }
        
        if (progress >= 100) {
            clearInterval(interval);
            
            try {
                if (currentStep.value === 1) {
                    const app = stats.value.today_appointments.find(a => a.title.includes('Stark') || (a.client && a.client.name.includes('Pepper')));
                    if (app) {
                        simStepLogs.value.push(`[Database] Updating Appointment #${app.id} status to 'completed'...`);
                        await axios.put(`/api/appointments/${app.id}`, {
                            client_id: app.client_id,
                            staff_id: app.staff_id,
                            title: app.title,
                            start_time: app.start_time,
                            end_time: app.end_time,
                            status: 'completed',
                            note: (app.note || '') + '\n[AI Summary]: Concluded successfully. Pepper Potts approved integration specifications.',
                            calendar_provider: app.calendar_provider || 'google'
                        });
                        simStepLogs.value.push('[Database] Appointment status updated successfully.');
                    }
                    
                    const deal = demoDeals.value.find(d => d.title.includes('Stark') || (d.company && d.company.name.includes('Stark')));
                    if (deal) {
                        simStepLogs.value.push(`[Database] Moving Deal #${deal.id} stage to 'closed_won'...`);
                        await axios.put(`/api/crm/deals/${deal.id}/stage`, {
                            stage: 'closed_won'
                        });
                        simStepLogs.value.push('[Database] Deal stage updated to Closed Won.');
                    }
                } else if (currentStep.value === 2) {
                    const app = stats.value.today_appointments.find(a => a.title.includes('Wayne') || (a.client && a.client.name.includes('Lucius')));
                    if (app) {
                        simStepLogs.value.push(`[Database] Updating Appointment #${app.id} status to 'confirmed'...`);
                        await axios.put(`/api/appointments/${app.id}`, {
                            client_id: app.client_id,
                            staff_id: app.staff_id,
                            title: app.title,
                            start_time: app.start_time,
                            end_time: app.end_time,
                            status: 'confirmed',
                            note: app.note,
                            calendar_provider: app.calendar_provider || 'google'
                        });
                        simStepLogs.value.push('[Database] Appointment status updated to Confirmed.');
                    }
                    
                    const deal = demoDeals.value.find(d => d.title.includes('Wayne') || (d.company && d.company.name.includes('Wayne')));
                    if (deal) {
                        simStepLogs.value.push(`[Database] Moving Deal #${deal.id} stage to 'interested'...`);
                        await axios.put(`/api/crm/deals/${deal.id}/stage`, {
                            stage: 'interested'
                        });
                        simStepLogs.value.push('[Database] Deal stage updated to Interested.');
                    }
                } else if (currentStep.value === 3) {
                    const app = stats.value.today_appointments.find(a => a.title.includes('Cyberdyne') || (a.client && a.client.name.includes('Miles')));
                    if (app) {
                        simStepLogs.value.push(`[Database] Updating Appointment #${app.id} status to 'completed'...`);
                        await axios.put(`/api/appointments/${app.id}`, {
                            client_id: app.client_id,
                            staff_id: app.staff_id,
                            title: app.title,
                            start_time: app.start_time,
                            end_time: app.end_time,
                            status: 'completed',
                            note: app.note,
                            calendar_provider: app.calendar_provider || 'google'
                        });
                        simStepLogs.value.push('[Database] Appointment status updated to Completed.');
                    }
                    
                    const deal = demoDeals.value.find(d => d.title.includes('Cyberdyne') || (d.company && d.company.name.includes('Cyberdyne')));
                    if (deal) {
                        simStepLogs.value.push(`[Database] Moving Deal #${deal.id} stage to 'booked'...`);
                        await axios.put(`/api/crm/deals/${deal.id}/stage`, {
                            stage: 'booked'
                        });
                        simStepLogs.value.push('[Database] Deal stage updated to Booked.');
                    }
                }
                
                simStepLogs.value.push('[System] All background triggers executed. Local database synced successfully!');
                await fetchStats();
                
            } catch (err) {
                console.error("Error during database simulation:", err);
                simStepLogs.value.push(`[Error] Simulation API request failed: ${err.message || err}`);
            } finally {
                isSimStepLoading.value = false;
            }
        }
    }, 100);
};

const nextSimulationStep = () => {
    if (currentStep.value < 3) {
        currentStep.value++;
        simStepProgress.value = 0;
        simStepLogs.value = [];
        simStepSummary.value = '';
    } else {
        simFinished.value = true;
    }
};

const closeStartDayModal = () => {
    showStartDayModal.value = false;
};

const triggerSimulation = async () => {
    isSimulating.value = true;
    try {
        const response = await axios.post('/api/demo/simulate');
        if (response.data.success) {
            triggerToast(`Booking simulated! Client "${response.data.appointment.client?.name}" booked "${response.data.appointment.title}" synced to Outlook.`);
            fetchStats();
        }
    } catch (error) {
        console.error("Simulation failed:", error);
        triggerToast("Simulation failed. Please check the backend log.");
    } finally {
        isSimulating.value = false;
    }
};

onMounted(() => {
    const userStr = localStorage.getItem('user');
    if (userStr) {
        currentUser.value = JSON.parse(userStr);
    }
    fetchStats();

    const onboardingCompleted = localStorage.getItem('onboarding_completed') === 'true';
    if (!onboardingCompleted && currentUser.value && currentUser.value.role === 'admin') {
        showOnboarding.value = true;
    }
});

const chartPath = computed(() => {
    const data = stats.value.appointments_breakdown;
    if (!data || data.length === 0) return '';
    
    const maxVal = Math.max(...data.map(d => d.count), 5);
    const width = 500;
    const height = 150;
    const padding = 20;
    const usableHeight = height - padding * 2;
    const usableWidth = width - padding * 2;
    
    const points = data.map((item, index) => {
        const x = padding + (index / (data.length - 1)) * usableWidth;
        const y = height - padding - (item.count / maxVal) * usableHeight;
        return { x, y };
    });
    
    let path = `M ${points[0].x} ${points[0].y}`;
    for (let i = 1; i < points.length; i++) {
        path += ` L ${points[i].x} ${points[i].y}`;
    }
    return path;
});

const chartAreaPath = computed(() => {
    const data = stats.value.appointments_breakdown;
    if (!data || data.length === 0) return '';
    
    const maxVal = Math.max(...data.map(d => d.count), 5);
    const width = 500;
    const height = 150;
    const padding = 20;
    const usableHeight = height - padding * 2;
    const usableWidth = width - padding * 2;
    
    const points = data.map((item, index) => {
        const x = padding + (index / (data.length - 1)) * usableWidth;
        const y = height - padding - (item.count / maxVal) * usableHeight;
        return { x, y };
    });
    
    let path = `M ${points[0].x} ${height - padding}`;
    path += ` L ${points[0].x} ${points[0].y}`;
    for (let i = 1; i < points.length; i++) {
        path += ` L ${points[i].x} ${points[i].y}`;
    }
    path += ` L ${points[points.length - 1].x} ${height - padding} Z`;
    return path;
});

const chartPoints = computed(() => {
    const data = stats.value.appointments_breakdown;
    if (!data || data.length === 0) return [];
    
    const maxVal = Math.max(...data.map(d => d.count), 5);
    const width = 500;
    const height = 150;
    const padding = 20;
    const usableHeight = height - padding * 2;
    const usableWidth = width - padding * 2;
    
    return data.map((item, index) => {
        const x = padding + (index / (data.length - 1)) * usableWidth;
        const y = height - padding - (item.count / maxVal) * usableHeight;
        
        const dateObj = new Date(item.date);
        const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'UTC' });
        
        return { x, y, value: item.count, label: formattedDate };
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        hour: 'numeric', 
        minute: '2-digit' 
    });
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit'
    });
};

const getInitials = (name) => {
    if (!name) return 'U';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const getAvatarColorClass = (id) => {
    const classes = ['bg-indigo', 'bg-purple', 'bg-teal', 'bg-pink', 'bg-orange', 'bg-blue'];
    return classes[(id || 0) % classes.length];
};

const getRelativeTime = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    return date.toLocaleDateString();
};

const getStatusClass = (status) => {
    switch(status) {
        case 'scheduled': return 'status-pill-scheduled';
        case 'confirmed': return 'status-pill-confirmed';
        case 'completed': return 'status-pill-completed';
        case 'cancelled': return 'status-pill-cancelled';
        default: return 'badge-secondary';
    }
};
</script>

<template>
    <!-- Action Toast Alert -->
    <div v-if="showToast" class="custom-toast-notification p-3 d-flex align-items-center justify-content-between" style="position: fixed; bottom: 80px; right: 24px; z-index: 1050; min-width: 320px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border-left: 4px solid var(--primary-color) !important;">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-bell text-indigo mr-2" style="font-size: 16px;"></i>
            <span class="text-sm font-weight-bold" style="color: var(--text-primary);">{{ toastMessage }}</span>
        </div>
        <button class="btn btn-xs text-muted" @click="showToast = false"><i class="fas fa-times"></i></button>
    </div>

    <!-- Action-First Dashboard Header -->
    <div class="content-header py-4">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="font-weight-extrabold mb-2 tracking-tight" style="font-size: 28px; color: var(--text-primary);">
                        {{ greeting }}, {{ currentUser?.name || 'User' }}!
                    </h1>
                    <!-- Main Action-Oriented Header Subtext -->
                    <div class="d-flex align-items-center mt-2">
                        <span class="badge px-3 py-2 mr-3" style="background: var(--primary-gradient); font-size: 14px; border-radius: 8px; font-weight: 700;">
                            {{ stats.today_count }} {{ stats.today_count === 1 ? 'appointment' : 'appointments' }} today
                        </span>
                        <span class="text-secondary text-sm">
                            {{ stats.today_count > 0 ? "You're all set to go. Let's make today productive!" : "Your day is clear! Share your booking link to fill slots." }}
                        </span>
                    </div>
                </div>
                <!-- Demo simulation trigger -->
                <div v-if="currentUser?.role === 'admin' || currentUser?.role === 'staff'" class="col-md-4 text-md-right mt-3 mt-md-0">
                    <button @click="triggerSimulation" class="btn btn-indigo" :disabled="isSimulating" style="border-radius: 12px; font-weight: 700; height: 46px;">
                        <i class="fas fa-magic mr-2" :class="{ 'fa-spin': isSimulating }"></i> 
                        <span>{{ isSimulating ? 'Simulating Booking...' : 'Simulate Booking & CRM Sync' }}</span>
                    </button>
                </div>
            </div>
            
            <!-- Quick-Start Navigation Controls -->
            <div class="row mt-4">
                <div class="col-12 d-flex flex-wrap gap-3">
                    <button @click="startDay" class="btn btn-primary d-flex align-items-center px-4" style="height: 48px; border-radius: 12px; font-weight: 700; gap: 8px;">
                        <i class="fas fa-play"></i> Start Day
                    </button>
                    <button @click="router.push('/admin/appointment')" class="btn btn-outline-secondary d-flex align-items-center px-4" style="height: 48px; border-radius: 12px; font-weight: 600; gap: 8px;">
                        <i class="fas fa-calendar-alt"></i> View Schedule
                    </button>
                    <button @click="router.push('/admin/crm')" class="btn btn-outline-secondary d-flex align-items-center px-4" style="height: 48px; border-radius: 12px; font-weight: 600; gap: 8px;">
                        <i class="fas fa-phone-alt"></i> Call Next Lead
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="content pb-5">
        <div class="container-fluid">
            <div v-if="isLoading" class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                <p class="text-muted">Loading your workspace...</p>
            </div>

            <div v-else class="row">
                <!-- Left Side: Today's Schedule & Upcoming Meetings -->
                <div class="col-lg-7">
                    <!-- Today's Agenda -->
                    <div class="card glass-card border-0 mb-4 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="font-weight-extrabold mb-1" style="font-size: 17px; color: var(--text-primary);">Today's Schedule</h5>
                                <p class="text-muted small mb-0">Your chronological agenda for today.</p>
                            </div>
                            <span class="text-xs text-muted font-weight-bold">{{ new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }) }}</span>
                        </div>

                        <!-- Chronological schedule grid -->
                        <div class="today-agenda-list">
                            <div v-if="stats.today_appointments && stats.today_appointments.length > 0">
                                <div v-for="app in stats.today_appointments" :key="app.id" class="agenda-item d-flex align-items-start mb-3 p-3" style="background-color: var(--bg-dark-hover); border-radius: 12px; border: 1px solid var(--border-dark);">
                                    <div class="agenda-time mr-3 pr-3 text-right" style="border-right: 2px solid var(--border-dark); min-width: 75px;">
                                        <span class="font-weight-bold d-block text-sm" style="color: var(--text-primary);">{{ formatTime(app.start_time) }}</span>
                                        <span class="text-xs text-muted">Start</span>
                                    </div>
                                    <div class="agenda-details flex-grow-1">
                                        <h6 class="font-weight-extrabold mb-1" style="font-size: 14.5px; color: var(--text-primary);">{{ app.title }}</h6>
                                        <div class="d-flex align-items-center text-xs text-muted gap-2 mb-2">
                                            <span><i class="far fa-user mr-1"></i>Client: {{ app.client?.name || 'Unknown' }}</span>
                                            <span>&bull;</span>
                                            <span><i class="fas fa-stethoscope mr-1"></i>Staff: {{ app.staff?.name || 'Nexa Host' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <span class="badge text-capitalize" :class="getStatusClass(app.status)">{{ app.status }}</span>
                                            <a v-if="app.meeting_link" :href="app.meeting_link" target="_blank" class="btn btn-xs btn-indigo py-1 px-2" style="font-size: 11px; border-radius: 6px; text-decoration: none;">
                                                <i class="fas fa-video mr-1"></i> Join Meeting
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Elegant Empty State -->
                            <div v-else class="empty-state-card border-0 py-5 text-center" style="background: rgba(17, 24, 39, 0.2); border-radius: 16px;">
                                <div class="mb-3">
                                    <i class="far fa-calendar-check fa-2x text-muted"></i>
                                </div>
                                <h6 class="font-weight-bold" style="color: var(--text-primary);">No appointments today</h6>
                                <p class="text-muted text-xs max-w-sm mx-auto px-4 mb-0">You have no scheduled bookings today. Put your scheduling link out on social media or email signatures!</p>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Meetings (Next 5) -->
                    <div class="card glass-card border-0 mb-4 p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="font-weight-extrabold mb-1" style="font-size: 17px; color: var(--text-primary);">Upcoming Meetings</h5>
                                <p class="text-muted small mb-0">Upcoming schedule bookings queued across all days.</p>
                            </div>
                        </div>

                        <div class="upcoming-list">
                            <div v-if="stats.recent_appointments.length > 0">
                                <div v-for="app in stats.recent_appointments" :key="app.id" class="d-flex align-items-center justify-content-between py-3 border-bottom" style="border-color: var(--border-dark) !important;">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials mr-3" :class="getAvatarColorClass(app.client?.id)" style="width: 38px; height: 38px; border-radius: 10px; font-weight: 700;">
                                            {{ getInitials(app.client?.name) }}
                                        </div>
                                        <div>
                                            <h6 class="font-weight-bold mb-0 text-sm" style="color: var(--text-primary);">{{ app.client?.name }}</h6>
                                            <p class="text-muted text-xs mb-1">{{ app.title }} with {{ app.staff?.name || 'Nexa Host' }}</p>
                                            <span class="text-xs font-weight-bold text-indigo"><i class="far fa-clock mr-1"></i>{{ formatDate(app.start_time) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="badge text-capitalize" :class="getStatusClass(app.status)">
                                            {{ app.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4 text-muted">
                                <i class="far fa-calendar-times mb-2 d-block"></i>
                                No upcoming bookings found.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: HubSpot/Notion-Style Recent Activity Timeline -->
                <div class="col-lg-5">
                    <div class="card glass-card border-0 p-4 mb-4" style="max-height: 520px; overflow-y: auto;">
                        <h5 class="font-weight-extrabold mb-1" style="font-size: 17px; color: var(--text-primary);">Recent Activity Feed</h5>
                        <p class="text-muted small mb-4">Real-time updates of deals, appointments, and contacts.</p>

                        <!-- Vertical Notion-style timeline -->
                        <div class="notion-timeline pl-3" style="position: relative; border-left: 2px solid var(--border-dark);">
                            <div v-if="stats.recent_activities && stats.recent_activities.length > 0">
                                <div v-for="act in stats.recent_activities" :key="act.id" class="timeline-event mb-4" style="position: relative;">
                                    <!-- Timeline Node Dot -->
                                    <div class="timeline-dot" style="position: absolute; left: -21px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background-color: var(--primary-color); border: 2px solid var(--bg-dark-card);"></div>
                                    
                                    <div class="timeline-content">
                                        <span class="text-xs text-muted d-block mb-1">{{ getRelativeTime(act.created_at) }}</span>
                                        <p class="text-sm mb-1" style="color: var(--text-primary); line-height: 1.4;">
                                            <span class="font-weight-bold text-capitalize" style="color: #ffffff;">{{ act.type }} Activity: </span>
                                            {{ act.description }}
                                        </p>
                                        <span v-if="act.contact" class="text-xs text-muted d-block">
                                            Contact: {{ act.contact.name }} ({{ act.contact.position || 'Client' }})
                                        </span>
                                        <span v-if="act.company" class="text-xs text-muted d-block">
                                            Company: {{ act.company.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-5 text-muted">
                                <i class="fas fa-list-ul mb-2 d-block"></i>
                                No recent activity logged.
                            </div>
                        </div>
                    </div>

                    <!-- Trend Chart inside tab/widget -->
                    <div class="card glass-card border-0 p-4">
                        <h5 class="font-weight-extrabold mb-1" style="font-size: 17px; color: var(--text-primary);">Weekly Booking Trend</h5>
                        <p class="text-muted small mb-4">Appointments scheduled during the past week.</p>

                        <div class="chart-container text-center pt-2">
                            <svg v-if="stats.appointments_breakdown.length > 0" viewBox="0 0 500 170" width="100%" class="svg-trend-chart">
                                <defs>
                                    <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#6366f1" stop-opacity="0.25"/>
                                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0"/>
                                    </linearGradient>
                                </defs>
                                
                                <path :d="chartAreaPath" fill="url(#areaGradient)" />
                                <path :d="chartPath" fill="none" stroke="#6366f1" stroke-width="3" stroke-linecap="round" />
                                
                                <line x1="20" y1="20" x2="480" y2="20" stroke="#1f2937" stroke-dasharray="4" />
                                <line x1="20" y1="85" x2="480" y2="85" stroke="#1f2937" stroke-dasharray="4" />
                                <line x1="20" y1="130" x2="480" y2="130" stroke="#374151" stroke-width="1.5" />
                                
                                <g v-for="(pt, idx) in chartPoints" :key="idx">
                                    <circle :cx="pt.x" :cy="pt.y" r="5" fill="#ffffff" stroke="#a855f7" stroke-width="3" />
                                    <text :x="pt.x" :y="pt.y - 10" font-size="9" text-anchor="middle" fill="#9ca3af" font-weight="700">
                                        {{ pt.value }}
                                    </text>
                                    <text :x="pt.x" :y="148" font-size="9.5" text-anchor="middle" fill="#6b7280" font-weight="500">
                                        {{ pt.label }}
                                    </text>
                                </g>
                            </svg>
                            <div v-else class="py-4 text-muted">
                                No data available to plot.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Onboarding Setup Wizard -->
    <onboarding-wizard v-if="showOnboarding" :user="currentUser" @close="showOnboarding = false" />

    <!-- Start Day Simulation Walkthrough Modal (Demo Mode Only) -->
    <div v-if="showStartDayModal" class="modal-backdrop-blur d-flex align-items-center justify-content-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(9, 13, 22, 0.85); backdrop-filter: blur(12px); z-index: 1050; padding: 20px;">
        <div class="card glass-card border-0 p-4" style="width: 100%; max-width: 650px; background: var(--bg-dark-card); border: 1px solid var(--border-dark) !important; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            <!-- Modal Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge px-2.5 py-1" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 6px; font-weight: 700; font-size: 11px;">DEMO MODE</span>
                    <h4 class="font-weight-extrabold m-0" style="font-size: 20px; color: var(--text-primary); letter-spacing: -0.5px;">
                        {{ simFinished ? 'Day Successfully Completed!' : 'Start Day Walkthrough Simulator' }}
                    </h4>
                </div>
                <button v-if="!isSimStepLoading" class="btn btn-link text-muted p-0" @click="closeStartDayModal" style="font-size: 18px;"><i class="fas fa-times"></i></button>
            </div>

            <!-- Walkthrough Body (Active Step) -->
            <div v-if="!simFinished">
                <!-- Step Indicator / Progress bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs font-weight-bold" style="color: var(--text-secondary);">STEP {{ currentStep }} OF 3: {{ getStepTitle(currentStep) }}</span>
                        <span class="text-xs text-indigo font-weight-bold">{{ simStepProgress }}% Complete</span>
                    </div>
                    <div class="progress-bar-container" style="height: 6px; background: rgba(255, 255, 255, 0.05); border-radius: 3px; overflow: hidden; position: relative;">
                        <div class="progress-bar-fill" :style="{ width: simStepProgress + '%' }" style="height: 100%; background: var(--primary-gradient); transition: width 0.1s linear; border-radius: 3px;"></div>
                    </div>
                </div>

                <!-- Step Description Details -->
                <div class="p-3 mb-4 d-flex align-items-center gap-3" style="background: var(--bg-dark-hover); border-radius: 12px; border: 1px solid var(--border-dark);">
                    <div class="step-icon-wrapper d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 10px; background: rgba(99, 102, 241, 0.1); color: var(--primary-color); font-size: 20px;">
                        <i :class="getStepIcon(currentStep)"></i>
                    </div>
                    <div>
                        <h6 class="font-weight-bold mb-1" style="color: var(--text-primary); font-size: 14.5px;">{{ getStepSub(currentStep) }}</h6>
                        <p class="text-muted text-xs mb-0">{{ getStepDesc(currentStep) }}</p>
                    </div>
                </div>

                <!-- Simulation Output Terminal -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs font-weight-bold" style="color: var(--text-secondary);"><i class="fas fa-terminal mr-1 text-muted"></i> Simulation Console Logs</span>
                        <span v-if="isSimStepLoading" class="text-xs text-muted"><i class="fas fa-spinner fa-spin mr-1"></i> Running pipeline...</span>
                    </div>
                    <div class="terminal-window p-3" style="background: #090d16; border-radius: 12px; border: 1px solid var(--border-dark); height: 160px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.6; color: #34d399;">
                        <div v-for="(log, idx) in simStepLogs" :key="idx" class="terminal-line">
                            <span class="text-muted mr-1.5">&gt;</span> {{ log }}
                        </div>
                        <div v-if="simStepLogs.length === 0" class="text-muted text-center py-4">
                            Click "Run Simulation Step" to run database updates, sync calendar, and trigger AI workflows.
                        </div>
                    </div>
                </div>

                <!-- AI Meeting Summary Output Box (Visible after completion of Step 1) -->
                <div v-if="currentStep === 1 && simStepProgress === 100 && simStepSummary" class="p-3 mb-4" style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px;">
                    <div class="d-flex align-items-center gap-2 mb-2 text-success" style="font-weight: 700; font-size: 13px;">
                        <i class="fas fa-brain"></i> AI Meeting Summary Sync
                    </div>
                    <p class="text-sm mb-0" style="color: var(--text-primary); line-height: 1.45; font-style: italic;">
                        "{{ simStepSummary }}"
                    </p>
                </div>

                <!-- Walkthrough Actions -->
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-secondary" :disabled="isSimStepLoading" @click="closeStartDayModal" style="border-radius: 10px; font-weight: 600;">Cancel</button>
                    <button class="btn btn-primary d-flex align-items-center gap-2" :disabled="isSimStepLoading || simStepProgress === 100" @click="executeSimulationStep" style="border-radius: 10px; font-weight: 700; background: var(--primary-gradient); border: 0;">
                        <i v-if="isSimStepLoading" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-play"></i>
                        <span>{{ isSimStepLoading ? 'Executing Database Queries...' : 'Run Simulation Step' }}</span>
                    </button>
                    <button v-if="simStepProgress === 100" class="btn btn-indigo" @click="nextSimulationStep" style="border-radius: 10px; font-weight: 700;">
                        <span>Next Step <i class="fas fa-arrow-right ml-1"></i></span>
                    </button>
                </div>
            </div>

            <!-- Walkthrough Success Completion Page -->
            <div v-else class="text-center py-4">
                <div class="success-ring mb-4 mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); border: 2px solid var(--accent-color); color: var(--accent-color); font-size: 36px;">
                    <i class="fas fa-check-double"></i>
                </div>
                <h5 class="font-weight-extrabold mb-2" style="font-size: 22px; color: var(--text-primary);">All Demo Steps Completed!</h5>
                <p class="text-muted text-sm max-w-md mx-auto mb-4">
                    The walkthrough simulated real database state updates for your morning schedule, synced to calendars, auto-graded leads, and moved CRM opportunities.
                </p>

                <!-- Simulated Summary Metrics -->
                <div class="row g-3 mb-4 mx-auto" style="max-width: 500px;">
                    <div class="col-6">
                        <div class="p-3 text-center" style="background: var(--bg-dark-hover); border-radius: 12px; border: 1px solid var(--border-dark);">
                            <span class="d-block text-xs text-muted mb-1">Appointments Handled</span>
                            <span class="font-weight-extrabold text-indigo" style="font-size: 20px;">3 / 3 Completed</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 text-center" style="background: var(--bg-dark-hover); border-radius: 12px; border: 1px solid var(--border-dark);">
                            <span class="d-block text-xs text-muted mb-1">Deals Closed/Advanced</span>
                            <span class="font-weight-extrabold text-success" style="font-size: 20px;">$250,000</span>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary px-4 py-2" @click="closeStartDayModal" style="border-radius: 10px; font-weight: 700; background: var(--primary-gradient); border: 0;">
                    Return to Dashboard
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.trend-up {
    color: #10b981;
    background-color: rgba(16, 185, 129, 0.1);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
}
.svg-trend-chart {
    max-height: 200px;
}
.svg-trend-chart circle {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}
.svg-trend-chart circle:hover {
    r: 7px;
    fill: #6366f1 !important;
    stroke: #ffffff !important;
    filter: drop-shadow(0 4px 6px rgba(99, 102, 241, 0.4));
}
.agenda-item {
    transition: var(--transition-fast);
}
.agenda-item:hover {
    border-color: #4b5563 !important;
    transform: translateX(4px);
}
.notion-timeline {
    padding-bottom: 8px;
}
.timeline-event:last-child {
    margin-bottom: 0 !important;
}
.timeline-dot {
    box-shadow: 0 0 0 4px var(--bg-dark-card);
    transition: var(--transition-fast);
}
.timeline-event:hover .timeline-dot {
    background-color: #a855f7 !important;
    transform: scale(1.2);
}
.bg-indigo { background-color: #6366f1; }
.bg-purple { background-color: #a855f7; }
.bg-teal { background-color: #14b8a6; }
.bg-pink { background-color: #ec4899; }
.bg-orange { background-color: #f97316; }
.bg-blue { background-color: #3b82f6; }

.modal-backdrop-blur {
    animation: fadeIn 0.25s ease-out forwards;
}
.progress-bar-fill {
    transition: width 0.1s linear;
}
.terminal-window::-webkit-scrollbar {
    width: 6px;
}
.terminal-window::-webkit-scrollbar-track {
    background: transparent;
}
.terminal-window::-webkit-scrollbar-thumb {
    background: #1f2937;
    border-radius: 3px;
}
.terminal-window::-webkit-scrollbar-thumb:hover {
    background: #374151;
}
@keyframes fadeIn {
    from { opacity: 0; backdrop-filter: blur(0px); }
    to { opacity: 1; backdrop-filter: blur(12px); }
}
@keyframes scaleUp {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.success-ring {
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
    animation: scaleUp 0.3s ease-out;
}
</style>