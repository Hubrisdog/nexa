<template>
    <div class="min-h-screen d-flex align-items-center justify-content-center py-5 px-3" style="background-color: var(--bg-dark-accent); color: var(--text-primary);">
        <div class="card glass-card border-0 p-4 shadow-lg w-100" style="max-width: 820px; border-radius: var(--border-radius-lg);">
            <div v-if="loadingProvider" class="text-center py-5">
                <i class="fas fa-circle-notch fa-spin fa-2x text-primary mb-3"></i>
                <p class="text-muted">Loading schedule profile...</p>
            </div>
            
            <div v-else-if="providerError" class="text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h4 class="font-weight-bold">Profile Unavailable</h4>
                <p class="text-muted">{{ providerError }}</p>
            </div>
            
            <div v-else-if="bookingSuccess" class="text-center py-5">
                <div class="success-icon-wrap bg-success-light text-success mx-auto mb-4" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="font-weight-extrabold tracking-tight mb-2" style="color: #ffffff;">Appointment Scheduled!</h3>
                <p class="text-secondary mb-4">A confirmation email along with a calendar invitation has been sent to <span class="font-weight-bold text-white">{{ bookingEmail }}</span>.</p>
                
                <div class="success-details-card text-left p-3 mb-4 mx-auto" style="max-width: 500px; background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark); border-radius: 12px;">
                    <div class="mb-2">
                        <span class="text-muted text-xs d-block">EVENT</span>
                        <span class="font-weight-bold" style="color: var(--text-primary);">{{ bookedAppointment?.title }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted text-xs d-block">HOST</span>
                        <span class="font-weight-bold" style="color: var(--text-primary);">{{ provider.name }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted text-xs d-block">DATE & TIME</span>
                        <span class="font-weight-bold text-indigo"><i class="far fa-calendar-alt mr-2"></i>{{ formatFullDateTime(bookedAppointment?.start_time) }}</span>
                    </div>
                    <div v-if="bookedAppointment?.meeting_link" class="mt-3 pt-3 border-top" style="border-color: var(--border-dark) !important;">
                        <span class="text-muted text-xs d-block mb-1">CONFERENCE LINK</span>
                        <a :href="bookedAppointment.meeting_link" target="_blank" class="btn btn-sm btn-indigo w-100 py-2 d-flex align-items-center justify-content-center gap-2" style="border-radius: 8px; text-decoration: none;">
                            <i class="fas fa-video"></i> Join Live Video Sync
                        </a>
                    </div>
                </div>
                
                <button class="btn btn-outline-secondary mt-3" @click="resetBookingFlow">Book Another Slot</button>
            </div>
            
            <div v-else class="row">
                <!-- Left Details Side -->
                <div class="col-md-5 border-right pr-md-4" style="border-color: var(--border-dark) !important;">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="avatar-initials" :class="getAvatarColorClass(provider.id)" style="width: 52px; height: 52px; border-radius: 14px; font-size: 20px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                            {{ getInitials(provider.name) }}
                        </div>
                        <div>
                            <span class="text-muted text-xs font-weight-bold uppercase tracking-wider">SCHEDULER</span>
                            <h4 class="font-weight-extrabold m-0 text-white" style="font-size: 19px; letter-spacing: -0.3px;">{{ provider.name }}</h4>
                        </div>
                    </div>
                    
                    <h2 class="font-weight-extrabold tracking-tight text-white mb-3" style="font-size: 22px; line-height: 1.3;">Nexa Discovery Sync</h2>
                    <p class="text-secondary text-sm mb-4">A standard 30-minute scoping discussion. Connect systems, discuss business details, and setup qualified CRM integrations.</p>
                    
                    <div class="d-flex flex-column gap-3 text-sm text-secondary">
                        <div class="d-flex align-items-center gap-2">
                            <i class="far fa-clock text-indigo" style="width: 18px;"></i>
                            <span>30 mins</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-globe text-indigo" style="width: 18px;"></i>
                            <span>{{ clientTimezone }}</span>
                        </div>
                        <div v-if="selectedDate" class="d-flex align-items-center gap-2 font-weight-bold text-indigo">
                            <i class="far fa-calendar text-indigo" style="width: 18px;"></i>
                            <span>{{ formatSelectedDate(selectedDate) }}</span>
                        </div>
                        <div v-if="selectedSlot" class="d-flex align-items-center gap-2 font-weight-bold text-indigo">
                            <i class="far fa-clock text-indigo" style="width: 18px;"></i>
                            <span>{{ selectedSlot.time_label }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Wizard Side -->
                <div class="col-md-7 pl-md-4 mt-4 mt-md-0">
                    <!-- Step 1: Select Date -->
                    <div v-if="step === 1">
                        <h5 class="font-weight-bold mb-3 text-white">Select Date</h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <input type="date" class="form-control form-control-modern w-100" v-model="selectedDate" :min="todayDateString" @change="fetchSlots" />
                            </div>
                        </div>
                        
                        <div v-if="loadingSlots" class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Loading slots...
                        </div>
                        
                        <div v-else-if="slots.length > 0">
                            <h6 class="text-xs font-weight-bold text-muted uppercase tracking-wider mb-2">Available Timeslots</h6>
                            <div class="slots-grid overflow-y-auto" style="max-height: 280px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                <button 
                                    v-for="slot in slots" 
                                    :key="slot.start" 
                                    class="btn py-2 px-3 text-sm text-center" 
                                    :class="slot.available ? 'btn-outline-indigo text-white' : 'btn-dark disabled text-muted'"
                                    :disabled="!slot.available"
                                    @click="selectSlot(slot)"
                                    style="border-radius: 8px; border-color: var(--border-dark);"
                                >
                                    {{ slot.time_label }}
                                </button>
                            </div>
                        </div>
                        
                        <div v-else-if="selectedDate" class="text-center py-5 text-muted border border-dashed" style="border-radius: 12px; border-color: var(--border-dark) !important;">
                            <i class="far fa-calendar-times fa-2x mb-2"></i>
                            <p class="text-sm m-0">No available timeslots on this day.</p>
                        </div>
                        
                        <div v-else class="text-center py-5 text-muted">
                            <p class="text-sm">Please pick a date to see availability.</p>
                        </div>
                    </div>
                    
                    <!-- Step 2: Input Details (1-Click Booking Form) -->
                    <div v-if="step === 2">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="font-weight-bold text-white mb-0">Confirm Details</h5>
                            <button class="btn btn-xs text-muted" @click="step = 1"><i class="fas fa-arrow-left mr-1"></i> Change Slot</button>
                        </div>
                        
                        <form @submit.prevent="submitBooking">
                            <div class="form-group mb-3">
                                <label class="form-label text-xs uppercase tracking-wider text-muted m-0 mb-1">Your Name</label>
                                <input type="text" v-model="form.name" class="form-control form-control-modern w-100" placeholder="Jane Doe" required />
                            </div>
                            
                            <div class="form-group mb-3">
                                <label class="form-label text-xs uppercase tracking-wider text-muted m-0 mb-1">Your Email</label>
                                <input type="email" v-model="form.email" class="form-control form-control-modern w-100" placeholder="jane.doe@example.com" required />
                            </div>
                            
                            <!-- Collapsible Additional Comments Section -->
                            <div class="mb-4">
                                <button type="button" class="btn btn-link text-indigo text-xs p-0 font-weight-bold d-flex align-items-center gap-1" @click="showComments = !showComments" style="text-decoration: none;">
                                    <i class="fas" :class="showComments ? 'fa-chevron-down' : 'fa-chevron-right'"></i> 
                                    <span>{{ showComments ? 'Hide Additional Comments' : 'Add Additional Comments (Optional)' }}</span>
                                </button>
                                
                                <div v-if="showComments" class="mt-2 transition-fast">
                                    <textarea v-model="form.notes" class="form-control form-control-modern w-100" rows="3" placeholder="Tell us anything that will help prepare for our meeting..."></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-indigo w-100 py-3 d-flex align-items-center justify-content-center gap-2" :disabled="bookingInProgress" style="border-radius: 10px; font-weight: 700;">
                                <i class="fas fa-calendar-check" v-if="!bookingInProgress"></i>
                                <i class="fas fa-spinner fa-spin" v-else></i>
                                <span>{{ bookingInProgress ? 'Scheduling Appointment...' : 'Schedule Appointment' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'PublicBook',
    data() {
        return {
            username: '',
            provider: {},
            loadingProvider: true,
            providerError: null,
            selectedDate: '',
            slots: [],
            loadingSlots: false,
            selectedSlot: null,
            step: 1,
            showComments: false,
            form: {
                name: '',
                email: '',
                notes: ''
            },
            bookingInProgress: false,
            bookingSuccess: false,
            bookedAppointment: null,
            bookingEmail: '',
            clientTimezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
        };
    },
    computed: {
        todayDateString() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    },
    created() {
        this.username = this.$route.params.username;
        this.fetchProvider();
    },
    methods: {
        async fetchProvider() {
            try {
                const response = await axios.get(`/api/public/booking/${this.username}`);
                this.provider = response.data;
            } catch (err) {
                console.error("Provider load failed", err);
                this.providerError = err.response?.data?.message || 'Could not load scheduling profile.';
            } finally {
                this.loadingProvider = false;
            }
        },
        async fetchSlots() {
            if (!this.selectedDate) return;
            this.loadingSlots = true;
            this.slots = [];
            try {
                const response = await axios.get(`/api/public/booking/${this.username}/slots`, {
                    params: {
                        date: this.selectedDate,
                        timezone: this.clientTimezone
                    }
                });
                this.slots = response.data;
            } catch (err) {
                console.error("Slots load failed", err);
            } finally {
                this.loadingSlots = false;
            }
        },
        selectSlot(slot) {
            this.selectedSlot = slot;
            this.step = 2;
        },
        async submitBooking() {
            this.bookingInProgress = true;
            try {
                const payload = {
                    provider_id: this.provider.id,
                    start_time: this.selectedSlot.start,
                    end_time: this.selectedSlot.end,
                    client_name: this.form.name,
                    client_email: this.form.email,
                    notes: this.form.notes
                };
                const response = await axios.post('/api/public/book', payload);
                if (response.data.success) {
                    this.bookingEmail = this.form.email;
                    this.bookedAppointment = response.data.appointment;
                    this.bookingSuccess = true;
                }
            } catch (err) {
                console.error("Booking failed", err);
                alert(err.response?.data?.message || 'Booking submission failed. Please select another slot.');
            } finally {
                this.bookingInProgress = false;
            }
        },
        resetBookingFlow() {
            this.bookingSuccess = false;
            this.bookedAppointment = null;
            this.selectedSlot = null;
            this.selectedDate = '';
            this.slots = [];
            this.step = 1;
            this.form = {
                name: '',
                email: '',
                notes: ''
            };
            this.showComments = false;
        },
        getInitials(name) {
            if (!name) return 'H';
            return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
        },
        getAvatarColorClass(id) {
            const classes = ['bg-indigo', 'bg-purple', 'bg-teal', 'bg-pink', 'bg-orange', 'bg-blue'];
            return classes[(id || 0) % classes.length];
        },
        formatSelectedDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
        },
        formatFullDateTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleString([], { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
    }
}
</script>

<style scoped>
.min-h-screen {
    min-height: 100vh;
}
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.btn-outline-indigo {
    color: #8b5cf6;
    border: 1px solid rgba(139, 92, 246, 0.4);
    background-color: rgba(139, 92, 246, 0.05);
}
.btn-outline-indigo:hover {
    background-color: rgba(139, 92, 246, 0.15);
    border-color: #8b5cf6;
    color: #ffffff !important;
}
.slots-grid::-webkit-scrollbar {
    width: 6px;
}
.slots-grid::-webkit-scrollbar-track {
    background: transparent;
}
.slots-grid::-webkit-scrollbar-thumb {
    background: var(--border-dark);
    border-radius: 3px;
}
</style>
