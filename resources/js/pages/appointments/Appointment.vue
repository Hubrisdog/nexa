<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const appointments = ref([]);
const pagination = ref({});
const search = ref('');
const statusFilter = ref('all');
const currentPage = ref(1);

const isDrawerOpen = ref(false);
const isEditing = ref(false);
const editingAppId = ref(null);

const clientsList = ref([]);
const staffList = ref([]);
const currentUser = ref(null);

const currentView = ref('week'); // 'list', 'month', 'week', 'day'
const calendarDate = ref(new Date());

const toastMessage = ref('');
const showToast = ref(false);

const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

const form = ref({
    client_id: '',
    staff_id: '',
    title: '',
    start_time: '',
    end_time: '',
    status: 'scheduled',
    note: '',
    calendar_provider: 'google'
});

const errors = ref({});
const isSubmitting = ref(false);

const fetchAppointments = async (page = 1) => {
    currentPage.value = page;
    let url = '/api/appointments?';
    if (['month', 'week', 'day'].includes(currentView.value)) {
        url += 'all=true';
    } else {
        url += `page=${page}`;
    }
    if (search.value) url += `&search=${encodeURIComponent(search.value)}`;
    if (statusFilter.value !== 'all') url += `&status=${statusFilter.value}`;
    
    try {
        const response = await axios.get(url);
        if (['month', 'week', 'day'].includes(currentView.value)) {
            appointments.value = response.data;
        } else {
            appointments.value = response.data.data;
            pagination.value = response.data;
        }
    } catch (error) {
        console.error("Error fetching appointments:", error);
    }
};

const fetchDropdowns = async () => {
    try {
        const clientsRes = await axios.get('/api/users?role=client&all=true');
        clientsList.value = clientsRes.data;

        const staffRes = await axios.get('/api/users?role=staff&all=true');
        staffList.value = staffRes.data;
    } catch (error) {
        console.error("Error fetching dropdown values:", error);
    }
};

const handleSearch = () => {
    fetchAppointments(1);
};

const handleStatusFilter = () => {
    fetchAppointments(1);
};

const aiSummary = ref(null);
const isSummarizing = ref(false);

const generateAiSummary = async () => {
    isSummarizing.value = true;
    aiSummary.value = null;
    try {
        const response = await axios.post(`/api/appointments/${editingAppId.value}/summarize`, {
            notes: form.value.note
        });
        aiSummary.value = response.data;
        triggerToast("AI Summary generated and synced to B2B CRM!");
    } catch (error) {
        console.error("Error generating AI summary:", error);
        triggerToast("Failed to generate summary. Verify API keys.");
    } finally {
        isSummarizing.value = false;
    }
};

const applyAiSummaryToNotes = () => {
    if (!aiSummary.value) return;
    let appended = `\n\n--- AI SUMMARY ---\n${aiSummary.value.summary}\n\nHighlights:\n`;
    aiSummary.value.highlights.forEach(h => appended += `- ${h}\n`);
    appended += `\nAction Items:\n`;
    aiSummary.value.action_items.forEach(a => appended += `- ${a}\n`);
    
    form.value.note += appended;
    aiSummary.value = null;
};

const openCreateDrawer = () => {
    isEditing.value = false;
    editingAppId.value = null;
    aiSummary.value = null;
    form.value = {
        client_id: currentUser.value?.role === 'client' ? currentUser.value.id : (clientsList.value.length > 0 ? clientsList.value[0].id : ''),
        staff_id: staffList.value.length > 0 ? staffList.value[0].id : '',
        title: '',
        start_time: '',
        end_time: '',
        status: 'scheduled',
        note: '',
        calendar_provider: 'google'
    };
    errors.value = {};
    isDrawerOpen.value = true;
};

const openEditDrawer = (app) => {
    isEditing.value = true;
    editingAppId.value = app.id;
    aiSummary.value = null;
    
    const startObj = new Date(app.start_time);
    const endObj = new Date(app.end_time);
    
    const formatLocalISO = (d) => {
        const pad = (n) => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    };

    form.value = {
        client_id: app.client_id,
        staff_id: app.staff_id,
        title: app.title,
        start_time: formatLocalISO(startObj),
        end_time: formatLocalISO(endObj),
        status: app.status,
        note: app.note || '',
        calendar_provider: app.calendar_provider || 'google'
    };
    errors.value = {};
    isDrawerOpen.value = true;
};

const closeDrawer = () => {
    isDrawerOpen.value = false;
};

const handleSubmit = async () => {
    isSubmitting.value = true;
    errors.value = {};
    try {
        if (isEditing.value) {
            await axios.put(`/api/appointments/${editingAppId.value}`, form.value);
            triggerToast("Rescheduled successfully! Confirmation email dispatched to client.");
        } else {
            await axios.post('/api/appointments', form.value);
            triggerToast("Appointment scheduled! Mock SMS & Email sent to client.");
        }
        closeDrawer();
        fetchAppointments(currentPage.value);
    } catch (error) {
        if (error.response && error.response.status === 422) {
            if (error.response.data.message) {
                errors.value = { booking: [error.response.data.message] };
            } else {
                errors.value = error.response.data.errors;
            }
        } else {
            console.error("Error saving appointment:", error);
        }
    } finally {
        isSubmitting.value = false;
    }
};

const changeStatusInline = async (app, newStatus) => {
    try {
        await axios.put(`/api/appointments/${app.id}`, {
            client_id: app.client_id,
            staff_id: app.staff_id,
            title: app.title,
            start_time: app.start_time,
            end_time: app.end_time,
            status: newStatus,
            note: app.note
        });
        triggerToast(`Status updated to ${newStatus}! Client notification sent.`);
        fetchAppointments(currentPage.value);
    } catch (error) {
        if (error.response && error.response.status === 422) {
            alert(error.response.data.message || 'Status update failed.');
        } else {
            console.error("Error updating status inline:", error);
        }
    }
};

const messageClient = (app) => {
    triggerToast(`Reminder details sent to ${app.client?.name} (${app.client?.email}) via mock SMS/WhatsApp.`);
};

const deleteApp = async (app) => {
    if (confirm(`Are you sure you want to delete this appointment for ${app.client?.name}?`)) {
        try {
            await axios.delete(`/api/appointments/${app.id}`);
            fetchAppointments(currentPage.value);
        } catch (error) {
            console.error("Error deleting appointment:", error);
        }
    }
};

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

const getInitials = (name) => {
    if (!name) return 'U';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const getAvatarColorClass = (id) => {
    const classes = ['bg-indigo', 'bg-purple', 'bg-teal', 'bg-pink', 'bg-orange', 'bg-blue'];
    return classes[(id || 0) % classes.length];
};

// --- Month View Computations ---
const calendarMonthName = computed(() => {
    return calendarDate.value.toLocaleString('default', { month: 'long', year: 'numeric' });
});

const calendarDays = computed(() => {
    const date = calendarDate.value;
    const year = date.getFullYear();
    const month = date.getMonth();

    const firstDay = new Date(year, month, 1);
    const startDayOfWeek = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const days = [];
    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({
            dayNumber: '',
            dateStr: null,
            isCurrentMonth: false,
            appointments: []
        });
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const pad = (n) => n.toString().padStart(2, '0');
        const dateStr = `${year}-${pad(month + 1)}-${pad(d)}`;

        const dayApps = appointments.value.filter(app => {
            if (!app.start_time) return false;
            return app.start_time.startsWith(dateStr);
        });

        days.push({
            dayNumber: d,
            dateStr,
            isCurrentMonth: true,
            appointments: dayApps
        });
    }

    return days;
});

const prevMonth = () => {
    const d = calendarDate.value;
    calendarDate.value = new Date(d.getFullYear(), d.getMonth() - 1, 1);
};

const nextMonth = () => {
    const d = calendarDate.value;
    calendarDate.value = new Date(d.getFullYear(), d.getMonth() + 1, 1);
};

// --- Week View Computations ---
const weekDays = computed(() => {
    const current = new Date(calendarDate.value);
    const day = current.getDay();
    // Move to current week's Monday
    const diff = current.getDate() - day + (day === 0 ? -6 : 1);
    const monday = new Date(current.setDate(diff));
    
    const days = [];
    for (let i = 0; i < 7; i++) {
        const d = new Date(monday);
        d.setDate(monday.getDate() + i);
        
        const pad = (n) => n.toString().padStart(2, '0');
        const dateStr = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        
        const dayApps = appointments.value.filter(app => {
            if (!app.start_time) return false;
            return app.start_time.startsWith(dateStr);
        });
        
        days.push({
            name: d.toLocaleString('en-US', { weekday: 'short' }),
            number: d.getDate(),
            dateStr,
            appointments: dayApps
        });
    }
    return days;
});

const weekRangeLabel = computed(() => {
    const days = weekDays.value;
    if (days.length === 0) return '';
    const startStr = new Date(days[0].dateStr).toLocaleDateString('default', { month: 'short', day: 'numeric' });
    const endStr = new Date(days[6].dateStr).toLocaleDateString('default', { month: 'short', day: 'numeric', year: 'numeric' });
    return `${startStr} - ${endStr}`;
});

const prevWeek = () => {
    const d = calendarDate.value;
    calendarDate.value = new Date(d.getFullYear(), d.getMonth(), d.getDate() - 7);
};

const nextWeek = () => {
    const d = calendarDate.value;
    calendarDate.value = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 7);
};

// --- Day View Computations ---
const dayLabel = computed(() => {
    return calendarDate.value.toLocaleDateString('default', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
});

const singleDayData = computed(() => {
    const d = calendarDate.value;
    const pad = (n) => n.toString().padStart(2, '0');
    const dateStr = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    
    const dayApps = appointments.value.filter(app => {
        if (!app.start_time) return false;
        return app.start_time.startsWith(dateStr);
    });
    
    return {
        name: d.toLocaleString('en-US', { weekday: 'short' }),
        number: d.getDate(),
        dateStr,
        appointments: dayApps
    };
});

const prevDay = () => {
    const d = calendarDate.value;
    calendarDate.value = new Date(d.getFullYear(), d.getMonth(), d.getDate() - 1);
};

const nextDay = () => {
    const d = calendarDate.value;
    calendarDate.value = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1);
};

const toggleView = (view) => {
    currentView.value = view;
    fetchAppointments(1);
    
    // Auto Scroll week grid on select
    if (['week', 'day'].includes(view)) {
        setTimeout(() => {
            const container = document.querySelector('.week-grid-scroll-container');
            if (container) {
                // Scroll to 8:00 AM (8 * 60px = 480px)
                container.scrollTop = 480;
            }
        }, 100);
    }
};

const clickDay = (day) => {
    if (!day.dateStr) return;
    openCreateDrawer();
    form.value.start_time = `${day.dateStr}T09:00`;
    form.value.end_time = `${day.dateStr}T09:30`;
};

const formatTimeOnly = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const d = new Date(dateTimeStr);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
};

// --- Position Calculator for vertical columns (1px = 1min, 24h = 1440px) ---
const getAptPositionStyle = (app) => {
    const start = new Date(app.start_time);
    const end = new Date(app.end_time);
    
    const startMins = start.getHours() * 60 + start.getMinutes();
    const endMins = end.getHours() * 60 + end.getMinutes();
    const duration = Math.max(30, endMins - startMins);
    
    return {
        top: `${startMins}px`,
        height: `${duration}px`
    };
};

const getAptPillClass = (status) => {
    switch (status) {
        case 'scheduled': return 'status-pill-scheduled';
        case 'confirmed': return 'status-pill-confirmed';
        case 'completed': return 'status-pill-completed';
        case 'cancelled': return 'status-pill-cancelled';
        default: return 'badge-secondary';
    }
};

// --- HTML5 Drag and Drop handlers ---
const handleDragStart = (e, app) => {
    e.dataTransfer.setData('appointment_id', app.id);
    e.dataTransfer.effectAllowed = 'move';
};

const handleDragOver = (e) => {
    e.preventDefault();
};

const handleDrop = async (e, day) => {
    e.preventDefault();
    const appId = e.dataTransfer.getData('appointment_id');
    if (!appId) return;

    const app = appointments.value.find(a => a.id == appId);
    if (!app) return;

    // Calculate Y coordinate within the slots container
    const rect = e.currentTarget.getBoundingClientRect();
    const clickY = e.clientY - rect.top;
    
    // Y-axis pixels maps to minutes (0 to 1440). Round to nearest 30 mins (30px)
    const droppedMins = Math.max(0, Math.min(1410, clickY));
    const roundedMins = Math.round(droppedMins / 30) * 30;

    const hour = Math.floor(roundedMins / 60);
    const minute = roundedMins % 60;

    // Preserve duration
    const originalStart = new Date(app.start_time);
    const originalEnd = new Date(app.end_time);
    const durationMs = originalEnd - originalStart;

    const pad = (n) => n.toString().padStart(2, '0');
    const newStartStr = `${day.dateStr}T${pad(hour)}:${pad(minute)}:00`;

    const newStart = new Date(newStartStr);
    const newEnd = new Date(newStart.getTime() + durationMs);
    const newEndStr = `${day.dateStr}T${pad(newEnd.getHours())}:${pad(newEnd.getMinutes())}:00`;

    try {
        await axios.put(`/api/appointments/${app.id}`, {
            client_id: app.client_id,
            staff_id: app.staff_id,
            title: app.title,
            start_time: newStartStr,
            end_time: newEndStr,
            status: app.status,
            note: app.note
        });
        triggerToast(`Rescheduled ${app.client?.name || 'Appointment'} to ${formatDate(newStartStr)}.`);
        fetchAppointments();
    } catch (err) {
        console.error("Drop reschedule failed", err);
        if (err.response && err.response.status === 422) {
            alert(err.response.data.message || 'Conflict detected. Slot is unavailable.');
        } else {
            alert('Could not reschedule appointment.');
        }
    }
};

onMounted(() => {
    const userStr = localStorage.getItem('user');
    if (userStr) {
        currentUser.value = JSON.parse(userStr);
    }
    fetchAppointments();
    fetchDropdowns();
    
    // Trigger scroll position logic
    setTimeout(() => {
        const container = document.querySelector('.week-grid-scroll-container');
        if (container) {
            container.scrollTop = 480;
        }
    }, 200);
});
</script>

<template>
    <!-- Notification Toast -->
    <div v-if="showToast" class="custom-toast-notification p-3 d-flex align-items-center justify-content-between" style="position: fixed; bottom: 80px; right: 24px; z-index: 1050; min-width: 320px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border-left: 4px solid var(--primary-color) !important;">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-bell text-indigo mr-2" style="font-size: 16px;"></i>
            <span class="text-sm font-weight-bold" style="color: var(--text-primary);">{{ toastMessage }}</span>
        </div>
        <button class="btn btn-xs text-muted" @click="showToast = false"><i class="fas fa-times"></i></button>
    </div>

    <div class="content-header py-4">
        <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h1 class="font-weight-extrabold mb-1 tracking-tight" style="font-size: 26px; color: var(--text-primary);">Appointments</h1>
                <p class="text-muted mb-0 text-sm">Review, schedule and drag-and-drop bookings, sync calendars, and manage pipeline opportunities.</p>
            </div>
            <div class="d-flex align-items-center mt-3 mt-md-0" style="gap: 12px;">
                <div class="btn-group shadow-sm" style="border-radius: 8px; overflow: hidden; border: 1px solid var(--border-dark);">
                    <button type="button" @click="toggleView('list')" class="btn btn-sm py-2 px-3" :class="currentView === 'list' ? 'btn-primary' : 'btn-outline-secondary'" style="font-weight: 600; font-size: 13.5px; border: none; border-radius: 0;">
                        <i class="fas fa-list mr-1"></i> List
                    </button>
                    <button type="button" @click="toggleView('day')" class="btn btn-sm py-2 px-3" :class="currentView === 'day' ? 'btn-primary' : 'btn-outline-secondary'" style="font-weight: 600; font-size: 13.5px; border: none; border-radius: 0;">
                        Day
                    </button>
                    <button type="button" @click="toggleView('week')" class="btn btn-sm py-2 px-3" :class="currentView === 'week' ? 'btn-primary' : 'btn-outline-secondary'" style="font-weight: 600; font-size: 13.5px; border: none; border-radius: 0;">
                        Week
                    </button>
                    <button type="button" @click="toggleView('month')" class="btn btn-sm py-2 px-3" :class="currentView === 'month' ? 'btn-primary' : 'btn-outline-secondary'" style="font-weight: 600; font-size: 13.5px; border: none; border-radius: 0;">
                        Month
                    </button>
                </div>
                <button @click="openCreateDrawer" class="btn btn-gradient px-4 py-2" style="height: 40px; display: inline-flex; align-items: center; border-radius: 8px;">
                    <i class="fas fa-plus mr-2"></i> Book Appointment
                </button>
            </div>
        </div>
    </div>

    <div class="content pb-5">
        <div class="container-fluid">
            <!-- Filter Bar (Only shown in list view) -->
            <div v-if="currentView === 'list'" class="row mb-4">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="input-group-custom shadow-sm">
                        <span class="input-icon"><i class="fas fa-search text-muted"></i></span>
                        <input 
                            v-model="search" 
                            @input="handleSearch" 
                            type="text" 
                            class="form-control-custom" 
                            placeholder="Search reservations by client name, provider name, or service title..."
                        >
                    </div>
                </div>
                <div class="col-md-4">
                    <select v-model="statusFilter" @change="handleStatusFilter" class="form-control shadow-sm" style="height: 48px; border-radius: 10px; border: 1px solid var(--border-dark); background-color: var(--bg-dark-hover) !important; color: var(--text-primary); font-weight: 500; font-size: 14.5px;">
                        <option value="all">All Reservation Statuses</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <!-- List View -->
            <div v-if="currentView === 'list'" class="card glass-card border-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 80px;">ID</th>
                                <th scope="col">Service / Title</th>
                                <th v-if="currentUser?.role !== 'client'" scope="col">Client Profile</th>
                                <th scope="col">Staff / Provider</th>
                                <th scope="col">Date & Time</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="app in appointments" :key="app.id" class="table-row-hover">
                                <td>{{ app.id }}</td>
                                <td class="font-weight-extrabold" style="color: var(--text-primary);">{{ app.title }}</td>
                                <td v-if="currentUser?.role !== 'client'">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials mr-3" :class="getAvatarColorClass(app.client?.id)">
                                            {{ getInitials(app.client?.name) }}
                                        </div>
                                        <div>
                                            <span class="font-weight-bold d-block mb-0 text-sm" style="color: var(--text-primary);">{{ app.client?.name }}</span>
                                            <span class="text-muted d-block small">{{ app.client?.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-weight-bold d-block text-sm" style="color: var(--text-secondary);">{{ app.staff?.name || '-' }}</span>
                                    <span class="text-muted d-block small" v-if="app.staff">{{ app.staff?.email }}</span>
                                </td>
                                <td>
                                    <span class="text-indigo font-weight-bold small">
                                        <i class="far fa-calendar-alt mr-1"></i> {{ formatDate(app.start_time) }}
                                    </span>
                                </td>
                                <td>
                                    <select 
                                        v-if="currentUser?.role !== 'client'"
                                        :value="app.status" 
                                        @change="changeStatusInline(app, $event.target.value)" 
                                        class="status-badge dropdown-status-btn font-weight-bold" 
                                        :class="'status-' + app.status"
                                        style="cursor: pointer; border: 1px solid var(--border-dark); padding-right: 22px; -webkit-appearance: none; -moz-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%223%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22 class=%22feather feather-chevron-down%22><polyline points=%226 9 12 15 18 9%22></polyline></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 10px;"
                                    >
                                        <option value="scheduled">Scheduled</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <span v-else class="status-badge" :class="'status-' + app.status">
                                        {{ app.status }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button @click="messageClient(app)" class="btn btn-sm btn-outline-secondary mr-2" style="border-radius: 8px; font-size: 13px;">
                                        <i class="fas fa-paper-plane"></i> Alert
                                    </button>
                                    <button @click="openEditDrawer(app)" class="btn btn-sm btn-outline-primary mr-2" style="border-radius: 8px; font-size: 13px;">
                                        <i class="far fa-edit"></i> Edit
                                    </button>
                                    <button @click="deleteApp(app)" class="btn btn-sm btn-outline-danger" style="border-radius: 8px; font-size: 13px;">
                                        <i class="far fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="appointments.length === 0">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="far fa-calendar-times fa-2x mb-2 d-block text-primary-light"></i>
                                    No appointment slots registered.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pagination.last_page > 1" class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center" style="border-radius: 0 0 16px 16px; padding: 18px 20px;">
                    <div class="text-muted small">
                        Showing page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </div>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': pagination.current_page === 1 }">
                            <a class="page-link bg-dark-hover border-dark text-white" href="#" @click.prevent="fetchAppointments(pagination.current_page - 1)">&laquo;</a>
                        </li>
                        <li v-for="page in pagination.last_page" :key="page" class="page-item" :class="{ 'active': pagination.current_page === page }">
                            <a class="page-link bg-dark-hover border-dark text-white" href="#" @click.prevent="fetchAppointments(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ 'disabled': pagination.current_page === pagination.last_page }">
                            <a class="page-link bg-dark-hover border-dark text-white" href="#" @click.prevent="fetchAppointments(pagination.current_page + 1)">&raquo;</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Month View -->
            <div v-else-if="currentView === 'month'" class="card glass-card border-0 p-4 animate-fade-in">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <button type="button" @click="prevMonth" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                        <i class="fas fa-chevron-left mr-1"></i> Prev Month
                    </button>
                    <h5 class="font-weight-bold mb-0 text-center text-white" style="font-size: 18px;">
                        {{ calendarMonthName }}
                    </h5>
                    <button type="button" @click="nextMonth" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                        Next Month <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>

                <div class="calendar-grid-header grid-cols-7 mb-2">
                    <div v-for="d in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="d" class="calendar-weekday-cell" style="padding: 10px; font-weight: 700; text-align: center; text-transform: uppercase; font-size: 11px; color: var(--text-secondary);">
                        {{ d }}
                    </div>
                </div>

                <div class="calendar-grid-body grid-cols-7 border-top border-left" style="border-color: var(--border-dark) !important;">
                    <div 
                        v-for="(day, index) in calendarDays" 
                        :key="index" 
                        class="calendar-day-cell border-right border-bottom" 
                        :class="{ 'empty': !day.dateStr }"
                        @click="clickDay(day)"
                        style="border-color: var(--border-dark) !important; min-height: 110px;"
                    >
                        <div class="calendar-day-number text-sm font-weight-bold" style="color: var(--text-secondary);">{{ day.dayNumber }}</div>
                        <div class="calendar-day-appointments-list mt-1">
                            <div 
                                v-for="app in day.appointments" 
                                :key="app.id" 
                                class="calendar-app-pill text-xs px-2 py-1 mb-1" 
                                :class="getAptPillClass(app.status)" 
                                style="border-radius: 4px; font-weight: 600; cursor: pointer; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;"
                                @click.stop="openEditDrawer(app)"
                            >
                                <span class="app-pill-time font-weight-bold mr-1">{{ formatTimeOnly(app.start_time) }}</span>
                                <span class="app-pill-title">{{ app.title }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Week View (with Drag and Drop + Quick Action hover items) -->
            <div v-else-if="currentView === 'week'" class="card glass-card border-0 p-4 animate-fade-in">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <button type="button" @click="prevWeek" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                        <i class="fas fa-chevron-left mr-1"></i> Prev Week
                    </button>
                    <h5 class="font-weight-bold mb-0 text-center text-white" style="font-size: 17px;">
                        {{ weekRangeLabel }}
                    </h5>
                    <button type="button" @click="nextWeek" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                        Next Week <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>

                <!-- Scrollable vertical week grid -->
                <div class="week-grid-scroll-container" style="max-height: 600px; overflow-y: auto; border: 1px solid var(--border-dark); border-radius: 12px;">
                    <div class="week-grid-container" style="min-width: 800px;">
                        <!-- Time labels left column -->
                        <div class="week-time-label-col">
                            <div class="week-day-header" style="height: 50px; background-color: rgba(17, 24, 39, 0.9); border-bottom: 2px solid var(--border-dark);"></div>
                            <div v-for="h in 24" :key="h" class="week-time-label-cell">
                                {{ h - 1 === 0 ? '12 AM' : (h - 1 < 12 ? (h - 1) + ' AM' : (h - 1 === 12 ? '12 PM' : (h - 1 - 12) + ' PM')) }}
                            </div>
                        </div>

                        <!-- Days columns -->
                        <div 
                            v-for="day in weekDays" 
                            :key="day.dateStr" 
                            class="week-day-col"
                            @dragover="handleDragOver"
                            @drop="handleDrop($event, day)"
                        >
                            <!-- Day Header -->
                            <div class="week-day-header">
                                <span class="week-day-name">{{ day.name }}</span>
                                <span class="week-day-number">{{ day.number }}</span>
                            </div>

                            <!-- Hour slots container (height 1440px) -->
                            <div class="week-day-slots">
                                <div 
                                    v-for="app in day.appointments" 
                                    :key="app.id" 
                                    class="week-slot-cell"
                                    :class="getAptPillClass(app.status)"
                                    :style="getAptPositionStyle(app)"
                                    draggable="true"
                                    @dragstart="handleDragStart($event, app)"
                                    @click="openEditDrawer(app)"
                                >
                                    <!-- Hover Quick Actions in corner -->
                                    <div class="week-slot-actions">
                                        <button @click.stop="changeStatusInline(app, 'cancelled')" class="btn btn-xs btn-danger p-0" title="Cancel Appointment" style="width:16px; height:16px; border-radius:4px; font-size:9px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button @click.stop="messageClient(app)" class="btn btn-xs btn-secondary p-0 text-white" title="Message Client" style="width:16px; height:16px; border-radius:4px; font-size:9px;">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button @click.stop="openEditDrawer(app)" class="btn btn-xs btn-primary p-0" title="Edit Time" style="width:16px; height:16px; border-radius:4px; font-size:9px;">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </div>

                                    <div>
                                        <span class="font-weight-bold d-block text-white" style="font-size: 10px; line-height: 1.1; margin-bottom: 2px;">{{ app.title }}</span>
                                        <span class="d-block opacity-75" style="font-size: 9px; line-height: 1;">{{ app.client?.name || 'Client' }}</span>
                                    </div>
                                    <span class="font-weight-bold opacity-90" style="font-size: 9px;">{{ formatTimeOnly(app.start_time) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Day View (Similar 24h column, but for a single day) -->
            <div v-else-if="currentView === 'day'" class="card glass-card border-0 p-4 animate-fade-in">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <button type="button" @click="prevDay" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                        <i class="fas fa-chevron-left mr-1"></i> Prev Day
                    </button>
                    <h5 class="font-weight-bold mb-0 text-center text-white" style="font-size: 17px;">
                        {{ dayLabel }}
                    </h5>
                    <button type="button" @click="nextDay" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                        Next Day <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>

                <!-- Single Day 24h grid -->
                <div class="week-grid-scroll-container" style="max-height: 600px; overflow-y: auto; border: 1px solid var(--border-dark); border-radius: 12px;">
                    <div class="week-grid-container" style="grid-template-columns: 80px 1fr; min-width: 400px;">
                        <!-- Time labels left column -->
                        <div class="week-time-label-col">
                            <div class="week-day-header" style="height: 50px; background-color: rgba(17, 24, 39, 0.9); border-bottom: 2px solid var(--border-dark);"></div>
                            <div v-for="h in 24" :key="h" class="week-time-label-cell">
                                {{ h - 1 === 0 ? '12 AM' : (h - 1 < 12 ? (h - 1) + ' AM' : (h - 1 === 12 ? '12 PM' : (h - 1 - 12) + ' PM')) }}
                            </div>
                        </div>

                        <!-- Single day column -->
                        <div 
                            class="week-day-col"
                            @dragover="handleDragOver"
                            @drop="handleDrop($event, singleDayData)"
                        >
                            <!-- Day Header -->
                            <div class="week-day-header" style="align-items: flex-start; padding-left: 20px;">
                                <span class="week-day-name">{{ singleDayData.name }}</span>
                                <span class="week-day-number" style="font-size: 18px;">{{ singleDayData.number }}</span>
                            </div>

                            <!-- Hour slots container (height 1440px) -->
                            <div class="week-day-slots">
                                <div 
                                    v-for="app in singleDayData.appointments" 
                                    :key="app.id" 
                                    class="week-slot-cell"
                                    :class="getAptPillClass(app.status)"
                                    :style="getAptPositionStyle(app)"
                                    draggable="true"
                                    @dragstart="handleDragStart($event, app)"
                                    @click="openEditDrawer(app)"
                                >
                                    <!-- Hover Quick Actions -->
                                    <div class="week-slot-actions">
                                        <button @click.stop="changeStatusInline(app, 'cancelled')" class="btn btn-xs btn-danger p-0" title="Cancel Appointment" style="width:16px; height:16px; border-radius:4px; font-size:9px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button @click.stop="messageClient(app)" class="btn btn-xs btn-secondary p-0 text-white" title="Message Client" style="width:16px; height:16px; border-radius:4px; font-size:9px;">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button @click.stop="openEditDrawer(app)" class="btn btn-xs btn-primary p-0" title="Edit Details" style="width:16px; height:16px; border-radius:4px; font-size:9px;">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </div>

                                    <div>
                                        <span class="font-weight-bold d-block text-white" style="font-size: 11px; line-height: 1.2; margin-bottom: 2px;">{{ app.title }}</span>
                                        <span class="d-block opacity-75" style="font-size: 10px;">With: {{ app.client?.name || 'Client' }} (Staff: {{ app.staff?.name || 'Host' }})</span>
                                        <span v-if="app.note" class="d-block text-xs mt-1 text-truncate opacity-80" style="max-width: 80%;">Note: {{ app.note }}</span>
                                    </div>
                                    <span class="font-weight-bold opacity-90 text-xs">{{ formatTimeOnly(app.start_time) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide-over Booking Drawer -->
            <div v-if="isDrawerOpen" class="drawer-backdrop" @click="closeDrawer"></div>
            <div class="drawer-right animate-slide-in" :class="{ 'open': isDrawerOpen }">
                <div class="drawer-header d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-extrabold mb-0 text-white" style="font-size: 18px;">
                        {{ isEditing ? 'Update Appointment' : 'Book Appointment' }}
                    </h5>
                    <button type="button" @click="closeDrawer" class="close border-0 bg-transparent text-white" style="font-size: 24px; outline: none;">&times;</button>
                </div>
                
                <form @submit.prevent="handleSubmit" class="d-flex flex-column h-100">
                    <div class="drawer-body p-4" style="overflow-y: auto; flex-grow: 1;">
                        <div v-if="errors.booking" class="alert alert-danger mb-3" style="border-radius: 8px; font-size: 14px;">
                            {{ errors.booking[0] }}
                        </div>

                        <div v-if="currentUser?.role !== 'client'" class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Client Name</label>
                            <select v-model="form.client_id" class="form-control form-control-modern w-100" required>
                                <option v-for="c in clientsList" :key="c.id" :value="c.id">{{ c.name }} ({{ c.email }})</option>
                            </select>
                            <div v-if="errors.client_id" class="error-feedback text-xs text-danger mt-1">{{ errors.client_id[0] }}</div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Select Staff / Provider</label>
                            <select v-model="form.staff_id" class="form-control form-control-modern w-100" required>
                                <option v-for="s in staffList" :key="s.id" :value="s.id">{{ s.name }} ({{ s.email }})</option>
                            </select>
                            <div v-if="errors.staff_id" class="error-feedback text-xs text-danger mt-1">{{ errors.staff_id[0] }}</div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Service Title</label>
                            <input v-model="form.title" type="text" class="form-control form-control-modern w-100" placeholder="Teeth Cleaning, Consultation, etc." required>
                            <div v-if="errors.title" class="error-feedback text-xs text-danger mt-1">{{ errors.title[0] }}</div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label text-xs uppercase tracking-wider text-muted">Start Date & Time</label>
                                <input v-model="form.start_time" type="datetime-local" class="form-control form-control-modern w-100" required>
                                <div v-if="errors.start_time" class="error-feedback text-xs text-danger mt-1">{{ errors.start_time[0] }}</div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label text-xs uppercase tracking-wider text-muted">End Date & Time</label>
                                <input v-model="form.end_time" type="datetime-local" class="form-control form-control-modern w-100" required>
                                <div v-if="errors.end_time" class="error-feedback text-xs text-danger mt-1">{{ errors.end_time[0] }}</div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Calendar Integration</label>
                            <select v-model="form.calendar_provider" class="form-control form-control-modern w-100" required>
                                <option value="google">Google Calendar</option>
                                <option value="outlook">Outlook / Teams Calendar</option>
                                <option value="none">None</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Reservation Status</label>
                            <select v-model="form.status" class="form-control form-control-modern w-100" required>
                                <option value="scheduled">Scheduled</option>
                                <option v-show="currentUser?.role !== 'client'" value="confirmed">Confirmed</option>
                                <option v-show="currentUser?.role !== 'client'" value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div v-if="errors.status" class="error-feedback text-xs text-danger mt-1">{{ errors.status[0] }}</div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Notes</label>
                            <textarea v-model="form.note" class="form-control form-control-modern w-100" placeholder="Add additional requirements or symptoms here..." style="height: 100px; resize: none;"></textarea>
                        </div>

                        <!-- AI Summarizer Widget -->
                        <div v-if="isEditing && currentUser?.role !== 'client'" class="mt-4 p-3 rounded-lg border" style="border-radius: 12px; border-color: var(--border-dark) !important; background-color: var(--bg-dark-hover);">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="font-weight-bold text-xs uppercase" style="font-weight: 700; color: var(--text-primary);"><i class="fas fa-magic text-indigo mr-1"></i> Gemini AI Companion</span>
                                <button type="button" @click="generateAiSummary" class="btn btn-xs btn-indigo" :disabled="isSummarizing || !form.note" style="border: 0; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                    <span v-if="isSummarizing"><i class="fas fa-spinner fa-spin"></i> Summarizing...</span>
                                    <span v-else>Generate Summary</span>
                                </button>
                            </div>
                            
                            <div v-if="aiSummary" class="mt-3 p-3 bg-dark border rounded-lg" style="font-size: 12px; border-color: var(--border-dark) !important;">
                                <div class="font-weight-bold text-white mb-1" style="font-weight: 700;">Summary:</div>
                                <p class="text-secondary mb-2">{{ aiSummary.summary }}</p>
                                
                                <div class="font-weight-bold text-white mb-1" style="font-weight: 700;">Key Highlights:</div>
                                <ul class="pl-3 mb-2" style="padding-left: 18px; color: var(--text-secondary);">
                                    <li v-for="h in aiSummary.highlights" :key="h">{{ h }}</li>
                                </ul>
                                
                                <div class="font-weight-bold text-white mb-1" style="font-weight: 700;">Action Items:</div>
                                <ul class="pl-3 mb-3" style="padding-left: 18px; color: var(--text-secondary);">
                                    <li v-for="a in aiSummary.action_items" :key="a">{{ a }}</li>
                                </ul>
                                
                                <button type="button" @click="applyAiSummaryToNotes" class="btn btn-xs btn-outline-indigo w-100" style="font-weight: 600; font-size: 11px; border-radius: 6px;">
                                    Append Summary to Note Fields
                                </button>
                            </div>
                            <div v-else class="text-muted text-xs text-center py-2" style="font-size: 11px;">
                                Provide meeting notes/transcripts above, then click Generate to summarize.
                            </div>
                        </div>
                    </div>

                    <div class="drawer-footer p-3 border-top d-flex justify-content-end gap-2" style="border-color: var(--border-dark) !important; background-color: rgba(17, 24, 39, 0.4);">
                        <button type="button" @click="closeDrawer" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-gradient px-4 py-2" style="border-radius: 8px;" :disabled="isSubmitting">
                            <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                            <span v-else>Confirm Slot</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gap-2 { gap: 8px; }
.calendar-weekday-cell {
    text-align: center;
}
.grid-cols-7 {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}
.calendar-day-cell {
    position: relative;
    cursor: pointer;
    transition: var(--transition-fast);
}
.calendar-day-cell:hover {
    background-color: var(--bg-dark-hover) !important;
}
.calendar-day-number {
    position: absolute;
    top: 6px;
    right: 8px;
}
.calendar-day-appointments-list {
    margin-top: 24px;
}
.calendar-app-pill {
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}
.week-grid-scroll-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.week-grid-scroll-container::-webkit-scrollbar-track {
    background: var(--bg-dark-accent);
}
.week-grid-scroll-container::-webkit-scrollbar-thumb {
    background: var(--border-dark);
    border-radius: 4px;
}
.week-grid-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #374151;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>