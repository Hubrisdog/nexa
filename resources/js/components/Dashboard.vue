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

const startDay = () => {
    if (stats.value.today_appointments.length > 0) {
        const firstApp = stats.value.today_appointments[0];
        triggerToast(`Starting your day! Your first appointment is with ${firstApp.client?.name || 'Client'} at ${formatTime(firstApp.start_time)}.`);
    } else {
        triggerToast("No appointments scheduled for today! Use your shareable link to book new clients.");
    }
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
</style>