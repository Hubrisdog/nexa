<template>
    <div class="custom-modal-overlay" style="z-index: 1200;">
        <div class="custom-modal p-4" style="background-color: var(--bg-dark-card); border: 1px solid var(--border-dark); border-radius: var(--border-radius-lg); width: 550px; max-width: 95%;">
            <!-- Progress Bar / Steps Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="text-xs uppercase tracking-wider text-muted font-weight-bold">SETUP WIZARD</span>
                <div class="d-flex gap-2">
                    <span v-for="s in 4" :key="s" class="step-dot" :class="{ 'active': step >= s, 'completed': step > s }"></span>
                </div>
            </div>

            <!-- Step 1: Branding Welcome -->
            <div v-if="step === 1" class="animate-fade-in">
                <h4 class="font-weight-extrabold text-white mb-2">Welcome to Nexa!</h4>
                <p class="text-secondary text-sm mb-4">Let's configure your scheduling portal branding in under 2 minutes.</p>
                
                <div class="form-group mb-4">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Portal Name</label>
                    <input v-model="form.app_name" type="text" class="form-control form-control-modern w-100" placeholder="e.g. Nexa Health" required />
                    <span class="text-xs text-muted d-block mt-1">This branding appears at the top left of your client panels.</span>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Notification Contact Email</label>
                    <input v-model="form.business_email" type="email" class="form-control form-control-modern w-100" placeholder="e.g. contact@nexa.com" required />
                </div>
                
                <button type="button" @click="nextStep" class="btn btn-indigo w-100 py-3 font-weight-bold" style="border-radius: 10px;">
                    Next: Setup Working Hours <i class="fas fa-arrow-right ml-1"></i>
                </button>
            </div>

            <!-- Step 2: Working Hours -->
            <div v-else-if="step === 2" class="animate-fade-in">
                <h4 class="font-weight-extrabold text-white mb-2">Configure Availability</h4>
                <p class="text-secondary text-sm mb-4">Define your standard workdays and hours. Nexa will use these to generate public timeslots.</p>
                
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">System Timezone</label>
                    <select v-model="form.timezone" class="form-control form-control-modern w-100">
                        <option value="UTC">UTC (Universal Coordinated)</option>
                        <option value="America/New_York">America/New_York (EST/EDT)</option>
                        <option value="America/Chicago">America/Chicago (CST/CDT)</option>
                        <option value="America/Denver">America/Denver (MST/MDT)</option>
                        <option value="America/Los_Angeles">America/Los_Angeles (PST/PDT)</option>
                        <option value="Europe/London">Europe/London (GMT/BST)</option>
                        <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                        <option value="Asia/Singapore">Asia/Singapore (SGT)</option>
                    </select>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Working Hours Range</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="time" v-model="form.work_start" class="form-control form-control-modern" />
                        <span class="text-muted">to</span>
                        <input type="time" v-model="form.work_end" class="form-control form-control-modern" />
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label text-xs uppercase tracking-wider text-muted mb-2">Available Workdays</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button 
                            v-for="(val, day) in form.days" 
                            :key="day" 
                            type="button" 
                            class="btn btn-sm py-2 px-3 text-capitalize" 
                            :class="val ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="form.days[day] = !form.days[day]"
                            style="border-radius: 8px;"
                        >
                            {{ day.substring(0, 3) }}
                        </button>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" @click="prevStep" class="btn btn-outline-secondary py-3 flex-grow-1" style="border-radius: 10px;">Back</button>
                    <button type="button" @click="nextStep" class="btn btn-indigo py-3 flex-grow-1 font-weight-bold" style="border-radius: 10px;">Next: Sync Calendars</button>
                </div>
            </div>

            <!-- Step 3: Calendar Sync -->
            <div v-else-if="step === 3" class="animate-fade-in">
                <h4 class="font-weight-extrabold text-white mb-2">Integrate Calendars</h4>
                <p class="text-secondary text-sm mb-4">Sync appointments bidirectionally with Google Calendar or Microsoft Outlook 365.</p>
                
                <div class="d-flex flex-column gap-3 mb-4">
                    <!-- Google Sync -->
                    <div class="p-3 d-flex justify-content-between align-items-center" style="background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark); border-radius: 12px;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fab fa-google text-danger" style="font-size: 24px;"></i>
                            <div>
                                <span class="font-weight-bold d-block text-white">Google Calendar Sync</span>
                                <span class="text-xs text-muted">Generate Meet links and block personal slots automatically.</span>
                            </div>
                        </div>
                        <button type="button" @click="form.sync_google = !form.sync_google" class="btn btn-xs px-3 py-2" :class="form.sync_google ? 'btn-success' : 'btn-outline-secondary'" style="border-radius: 6px;">
                            {{ form.sync_google ? 'Connected' : 'Connect' }}
                        </button>
                    </div>

                    <!-- Outlook Sync -->
                    <div class="p-3 d-flex justify-content-between align-items-center" style="background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark); border-radius: 12px;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fab fa-windows text-info" style="font-size: 24px;"></i>
                            <div>
                                <span class="font-weight-bold d-block text-white">Outlook & Teams Link Sync</span>
                                <span class="text-xs text-muted">Import work events and auto-generate Teams channels.</span>
                            </div>
                        </div>
                        <button type="button" @click="form.sync_outlook = !form.sync_outlook" class="btn btn-xs px-3 py-2" :class="form.sync_outlook ? 'btn-success' : 'btn-outline-secondary'" style="border-radius: 6px;">
                            {{ form.sync_outlook ? 'Connected' : 'Connect' }}
                        </button>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" @click="prevStep" class="btn btn-outline-secondary py-3 flex-grow-1" style="border-radius: 10px;">Back</button>
                    <button type="button" @click="nextStep" class="btn btn-indigo py-3 flex-grow-1 font-weight-bold" style="border-radius: 10px;">Next: Shareable Links</button>
                </div>
            </div>

            <!-- Step 4: Shareable Link -->
            <div v-else-if="step === 4" class="animate-fade-in">
                <h4 class="font-weight-extrabold text-white mb-2">Your Booking Link is Ready!</h4>
                <p class="text-secondary text-sm mb-4">Share this link directly in email signatures or website CTAs to accept bookings.</p>
                
                <div class="p-3 mb-4 d-flex align-items-center justify-content-between text-indigo font-weight-bold" style="background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark); border-radius: 12px; font-size: 14px; word-break: break-all;">
                    <span>{{ getShareableLink }}</span>
                    <button type="button" @click="copyLink" class="btn btn-xs btn-outline-indigo ml-2 py-2 px-3" style="border-radius: 6px;">
                        <i class="far fa-copy mr-1"></i> {{ copied ? 'Copied!' : 'Copy' }}
                    </button>
                </div>

                <button type="button" @click="finishSetup" class="btn btn-primary w-100 py-3 font-weight-bold" style="border-radius: 10px; background: var(--primary-gradient);">
                    <i class="fas fa-check-circle mr-2"></i> Launch Portal
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'OnboardingWizard',
    props: {
        user: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            step: 1,
            copied: false,
            form: {
                app_name: 'Nexa App',
                business_email: this.user?.email || 'admin@example.com',
                timezone: 'America/New_York',
                work_start: '09:00',
                work_end: '17:00',
                days: {
                    monday: true,
                    tuesday: true,
                    wednesday: true,
                    thursday: true,
                    friday: true,
                    saturday: false,
                    sunday: false
                },
                sync_google: false,
                sync_outlook: false
            }
        };
    },
    computed: {
        getShareableLink() {
            if (!this.user?.name) return 'http://127.0.0.1:8000/book/admin';
            const slug = this.user.name.toLowerCase().replace(/\s+/g, '-');
            return `${window.location.origin}/book/${slug}`;
        }
    },
    methods: {
        nextStep() {
            this.step++;
        },
        prevStep() {
            this.step--;
        },
        copyLink() {
            navigator.clipboard.writeText(this.getShareableLink);
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 2500);
        },
        async finishSetup() {
            try {
                // 1. Update general settings
                await axios.post('/api/settings', {
                    app_name: this.form.app_name,
                    business_email: this.form.business_email
                });

                // 2. Format and update availability working hours
                const workingHours = {};
                Object.keys(this.form.days).forEach(day => {
                    if (this.form.days[day]) {
                        workingHours[day] = {
                            start: this.form.work_start,
                            end: this.form.work_end
                        };
                    }
                });

                await axios.post('/api/availability', {
                    working_hours: workingHours,
                    breaks: [{ start: '12:00', end: '13:00' }],
                    buffer_time: 15,
                    timezone: this.form.timezone
                });

                localStorage.setItem('onboarding_completed', 'true');
                this.$emit('close');
            } catch (err) {
                console.error("Failed completing onboarding wizard setup", err);
                alert("Setup failed to synchronize details. Launching anyway.");
                localStorage.setItem('onboarding_completed', 'true');
                this.$emit('close');
            }
        }
    }
}
</script>

<style scoped>
.step-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--border-dark);
    transition: var(--transition-fast);
}
.step-dot.active {
    background-color: var(--primary-color);
    box-shadow: 0 0 8px rgba(99, 102, 241, 0.6);
}
.step-dot.completed {
    background-color: #10b981;
}
.gap-2 { gap: 8px; }
.btn-outline-indigo {
    color: #8b5cf6;
    border: 1px solid rgba(139, 92, 246, 0.4);
}
.btn-outline-indigo:hover {
    background-color: rgba(139, 92, 246, 0.1);
    border-color: #8b5cf6;
}
.animate-fade-in {
    animation: fadeIn 0.25s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
