<template>
    <div class="min-h-screen d-flex align-items-center justify-content-center py-5 px-3" :style="{ '--primary-color': brandColor, '--primary-rgb': brandRgb, 'background-color': 'var(--bg-dark-accent)', 'color': 'var(--text-primary)' }">
        <div class="card glass-card border-0 p-4 shadow-lg w-100" style="max-width: 820px; border-radius: var(--border-radius-lg);">
            
            <!-- Admin Dashboard Shortcut Banner -->
            <div v-if="isAuthenticated" class="mb-4 p-3 rounded-lg border d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: rgba(139, 92, 246, 0.08); border-color: rgba(139, 92, 246, 0.25) !important;">
                <div class="text-left">
                    <span class="badge text-xs py-1 px-2.5 mr-2 text-white" style="background: var(--primary-color); font-weight: 700; border-radius: 6px;">ADMINISTRATOR</span>
                    <span class="text-sm font-weight-bold text-white">Logged in as {{ currentUser?.name || 'Admin User' }}</span>
                    <p class="text-muted text-xs mb-0 mt-0.5">You are viewing your public workspace scheduling page.</p>
                </div>
                <router-link to="/admin/dashboard" class="btn btn-sm btn-indigo px-3 py-2 d-flex align-items-center gap-2" style="border-radius: 8px; font-weight: 700; background: var(--primary-color); border: none; font-size: 12.5px;">
                    <i class="fas fa-cog"></i>
                    <span>Open Admin Dashboard</span>
                </router-link>
            </div>
            
            <!-- Demo Mode Alert Banner & Magic Booking Simulator controls -->
            <div v-if="isDemoMode" class="mb-4 p-3 rounded-lg border d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: rgba(99, 102, 241, 0.08); border-color: rgba(99, 102, 241, 0.25) !important;">
                <div class="text-left">
                    <span class="badge badge-indigo text-xs py-1 px-2.5 mr-2 animate-pulse text-white" style="background: var(--primary-color);">SIMULATOR</span>
                    <span class="text-sm font-weight-bold text-white">Interactive Demo Mode</span>
                    <p class="text-muted text-xs mb-0 mt-0.5">
                        {{ magicBookingLogs.length > 0 ? magicBookingLogs[magicBookingLogs.length - 1] : 'Explore B2B CRM pipeline auto-creation and calendar synchronization in real-time.' }}
                    </p>
                </div>
                <button @click="triggerMagicBooking" :disabled="bookingInProgress || magicBookingInProgress" class="btn btn-sm btn-indigo px-3 py-2 d-flex align-items-center gap-2" style="border-radius: 8px; font-weight: 700;">
                    <i class="fas fa-magic" :class="{ 'fa-spin': magicBookingInProgress }"></i>
                    <span>{{ magicBookingInProgress ? 'Simulating...' : '⚡ Magic Auto-Fill & Book' }}</span>
                </button>
            </div>
            
            <!-- Loading State -->
            <div v-if="loadingProvider" class="text-center py-5">
                <i class="fas fa-circle-notch fa-spin fa-2x text-indigo mb-3"></i>
                <p class="text-muted">Loading schedule profile...</p>
            </div>
            
            <!-- Error State -->
            <div v-else-if="providerError" class="text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h4 class="font-weight-bold">Profile Unavailable</h4>
                <p class="text-muted">{{ providerError }}</p>
            </div>

            <!-- Workspace Staff Selection Screen (If multiple providers) -->
            <div v-else-if="showStaffSelector" class="text-center py-4">
                <div class="mb-4">
                    <img v-if="branding.logo_path" :src="'/' + branding.logo_path" style="max-height: 70px; max-width: 180px; object-fit: contain;" class="mb-3" />
                    <div v-else class="logo-circle mb-3 mx-auto" style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--primary-color);">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 class="font-weight-extrabold text-white mb-1">{{ branding.name }}</h3>
                    <p class="text-secondary text-sm">Select a team member to schedule an appointment with.</p>
                </div>

                <div class="d-flex flex-column gap-3 mx-auto" style="max-width: 480px;">
                    <!-- Collective Team Booking Option -->
                    <div 
                        class="d-flex align-items-center justify-content-between p-3 rounded-lg border hover-card mb-2" 
                        style="background-color: var(--bg-dark-hover); border-color: var(--primary-color) !important; cursor: pointer; transition: all 0.2s;"
                        @click="selectTeamProvider"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-initials bg-indigo" style="width: 44px; height: 44px; border-radius: 10px; font-size: 16px; font-weight: 800; display: flex; align-items: center; justify-content: center; background: var(--primary-color) !important;">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div class="text-left">
                                <span class="font-weight-bold text-white text-sm d-block">Collective Team Booking</span>
                                <span class="text-xs text-muted d-block mt-0.5">Route dynamically to the next available staff member</span>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-indigo px-3 py-1.5" style="border-radius: 8px; font-size: 12px; font-weight: 500;">
                            Book with Team
                        </button>
                    </div>

                    <div 
                        v-for="p in providers" 
                        :key="p.id" 
                        class="d-flex align-items-center justify-content-between p-3 rounded-lg border hover-card" 
                        style="background-color: var(--bg-dark-hover); border-color: var(--border-dark) !important; cursor: pointer; transition: all 0.2s;"
                        @click="selectProvider(p)"
                    >
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-initials" :class="getAvatarColorClass(p.id)" style="width: 44px; height: 44px; border-radius: 10px; font-size: 16px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                                {{ getInitials(p.name) }}
                            </div>
                            <div class="text-left">
                                <span class="font-weight-bold text-white text-sm d-block">{{ p.name }}</span>
                                <span class="text-xs text-muted d-block mt-0.5">{{ p.email }}</span>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-indigo px-3 py-1.5" style="border-radius: 8px; font-size: 12px; font-weight: 500;">
                            Book Slot
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Booking Success State -->
            <div v-else-if="bookingSuccess" class="text-center py-5">
                <div class="success-icon-wrap bg-success-light text-success mx-auto mb-4" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; background-color: rgba(16, 185, 129, 0.1);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="font-weight-extrabold tracking-tight mb-2" style="color: #ffffff;">Appointment Scheduled!</h3>
                <p class="text-secondary mb-4">A confirmation email has been sent to <span class="font-weight-bold text-white">{{ bookingEmail }}</span>.</p>
                
                <div class="success-details-card text-left p-4 mb-4 mx-auto" style="max-width: 500px; background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark); border-radius: 16px;">
                    <div class="mb-3">
                        <span class="text-muted text-xs d-block uppercase font-weight-bold tracking-wider">EVENT</span>
                        <span class="font-weight-bold" style="color: var(--text-primary); font-size: 16px;">{{ bookedAppointment?.title }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted text-xs d-block uppercase font-weight-bold tracking-wider">HOST</span>
                        <span class="font-weight-bold" style="color: var(--text-primary); font-size: 15px;">{{ provider.name }}</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted text-xs d-block uppercase font-weight-bold tracking-wider">DATE & TIME</span>
                        <span class="font-weight-bold text-indigo" style="font-size: 15px;"><i class="far fa-calendar-alt mr-2"></i>{{ formatFullDateTime(bookedAppointment?.start_time) }}</span>
                    </div>
                    <div v-if="bookedAppointment?.meeting_link" class="mt-3 pt-3 border-top" style="border-color: var(--border-dark) !important;">
                        <span class="text-muted text-xs d-block mb-2 uppercase font-weight-bold tracking-wider">CONFERENCE LINK</span>
                        <a :href="bookedAppointment.meeting_link" target="_blank" class="btn btn-sm btn-indigo w-100 py-2 d-flex align-items-center justify-content-center gap-2 mb-3" style="border-radius: 8px; text-decoration: none;">
                            <i class="fas fa-video"></i> Join Live Video Sync
                        </a>
                    </div>
                    
                    <div class="mt-3 pt-3 border-top" style="border-color: var(--border-dark) !important;">
                        <span class="text-muted text-xs d-block mb-2 uppercase font-weight-bold tracking-wider">ADD TO CALENDAR</span>
                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <a :href="googleCalendarUrl" target="_blank" class="btn btn-sm btn-dark border-secondary w-100 py-2 text-white d-flex align-items-center justify-content-center gap-2" style="border-radius: 8px; text-decoration: none;">
                                <i class="fab fa-google"></i> Google Calendar
                            </a>
                            <a :href="`/api/public/appointments/${bookedAppointment?.id}/ics`" class="btn btn-sm btn-dark border-secondary w-100 py-2 text-white d-flex align-items-center justify-content-center gap-2" style="border-radius: 8px; text-decoration: none;">
                                <i class="far fa-calendar-plus"></i> Download iCal / ICS
                            </a>
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-outline-secondary mt-3" @click="resetBookingFlow">Book Another Slot</button>
            </div>
            
            <!-- Standard Scheduler State -->
            <div v-else class="row">
                <!-- Left Details Side -->
                <div class="col-md-5 border-right pr-md-4" style="border-color: var(--border-dark) !important;">
                    <button v-if="providers.length > 1" @click="goBackToStaff" class="btn btn-xs text-muted mb-3 p-0 border-0 bg-transparent text-xs d-flex align-items-center gap-1">
                        <i class="fas fa-chevron-left"></i> Back to team
                    </button>

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img v-if="branding.logo_path" :src="'/' + branding.logo_path" style="width: 52px; height: 52px; border-radius: 14px; object-fit: contain; background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark);" />
                        <div v-else class="avatar-initials" :class="getAvatarColorClass(provider.id)" style="width: 52px; height: 52px; border-radius: 14px; font-size: 20px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                            {{ getInitials(provider.name) }}
                        </div>
                        <div>
                            <span class="text-muted text-xs font-weight-bold uppercase tracking-wider">SCHEDULER</span>
                            <h4 class="font-weight-extrabold m-0 text-white" style="font-size: 19px; letter-spacing: -0.3px;">{{ provider.name }}</h4>
                        </div>
                    </div>
                    
                    <h2 class="font-weight-extrabold tracking-tight text-white mb-3" style="font-size: 22px; line-height: 1.3;">{{ branding.name || 'Nexa' }} Discovery Sync</h2>
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

        <!-- Subtle Admin Access Portal Link -->
        <div class="w-100 text-center mt-4" style="z-index: 10;">
            <router-link to="/login" class="text-muted text-xs d-flex align-items-center justify-content-center gap-1 hover-light" style="text-decoration: none; transition: all 0.2s;">
                <i class="fas fa-lock" style="font-size: 10px;"></i>
                <span>Admin Access Portal</span>
            </router-link>
        </div>

        <!-- Floating AI Assistant Chat Widget -->
        <div v-if="isDemoMode" class="ai-assistant-widget" style="position: fixed; bottom: 30px; right: 30px; z-index: 1000; font-family: sans-serif;">
            <!-- Floating Chat Icon Trigger -->
            <button v-if="!showAiChat" @click="showAiChat = true" class="btn btn-primary d-flex align-items-center justify-content-center shadow-lg" style="width: 56px; height: 56px; border-radius: 50%; background: var(--primary-gradient); border: 0; box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4) !important;">
                <i class="fas fa-robot text-white" style="font-size: 24px;"></i>
            </button>

            <!-- Chat Container -->
            <div v-else class="card shadow-2xl border-0" style="width: 360px; height: 480px; background: var(--bg-dark-card); border: 1px solid var(--border-dark) !important; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; animation: slideIn 0.3s ease-out;">
                <!-- Header -->
                <div class="p-3 d-flex justify-content-between align-items-center border-bottom" style="background: rgba(99, 102, 241, 0.08); border-color: var(--border-dark) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center bg-indigo" style="width: 32px; height: 32px; border-radius: 50%;">
                            <i class="fas fa-robot text-white text-sm"></i>
                        </div>
                        <div>
                            <h6 class="font-weight-extrabold m-0 text-white text-sm">Nexa AI Assistant</h6>
                            <span class="text-xs text-success font-weight-semibold"><i class="fas fa-circle mr-1" style="font-size: 8px;"></i> Online</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-link text-muted p-0" @click="showAiChat = false" style="font-size: 16px;"><i class="fas fa-times"></i></button>
                </div>

                <!-- Chat Message Window -->
                <div class="chat-messages flex-grow-1 p-3 overflow-y-auto" ref="chatMessages" style="background: var(--bg-dark-accent); display: flex; flex-direction: column; gap: 12px; height: 260px;">
                    <div v-for="(msg, idx) in chatHistory" :key="idx" class="d-flex" :class="msg.sender === 'user' ? 'justify-content-end' : 'justify-content-start'">
                        <div class="p-2.5 max-w-sm text-sm" :style="msg.sender === 'user' ? 'background: var(--primary-color); color: white; border-radius: 12px 12px 0 12px;' : 'background: var(--bg-dark-hover); color: var(--text-primary); border-radius: 12px 12px 12px 0; border: 1px solid var(--border-dark);'">
                            <span v-if="msg.loading"><i class="fas fa-spinner fa-spin mr-1"></i> Thinking...</span>
                            <span v-else>{{ msg.text }}</span>
                        </div>
                    </div>
                </div>

                <!-- Chat Suggestions / Predefined Quick Options -->
                <div v-if="!aiProcessing" class="px-3 py-2 border-top" style="border-color: var(--border-dark) !important; background: rgba(9, 13, 22, 0.4) !important;">
                    <span class="text-xs text-muted font-weight-bold d-block mb-1.5">Quick Actions:</span>
                    <div class="d-flex flex-wrap gap-1.5">
                        <button 
                            type="button"
                            v-for="opt in chatSuggestions" 
                            :key="opt.text" 
                            @click="executeAiSuggestion(opt)" 
                            class="btn btn-xs btn-outline-indigo py-1 px-2" 
                            style="font-size: 11px; border-radius: 6px; text-align: left;"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Input Form -->
                <div class="p-2 border-top" style="border-color: var(--border-dark) !important; background: var(--bg-dark-card);">
                    <form @submit.prevent="submitChatMessage" class="d-flex gap-2">
                        <input type="text" v-model="chatInput" placeholder="Ask Nexa Assistant..." class="form-control form-control-sm form-control-modern flex-grow-1" :disabled="aiProcessing" style="border-radius: 8px; font-size: 13px;" />
                        <button type="submit" class="btn btn-sm btn-indigo" :disabled="aiProcessing || !chatInput.trim()" style="border-radius: 8px; width: 38px; height: 38px; padding: 0;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

const hexToRgb = (hex) => {
    if (!hex) return '139, 92, 246';
    const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
    const fullHex = hex.replace(shorthandRegex, (m, r, g, b) => r + r + g + g + b + b);
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(fullHex);
    return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '139, 92, 246';
};

export default {
    name: 'PublicBook',
    data() {
        return {
            isAuthenticated: localStorage.getItem('auth') === 'true',
            currentUser: (() => {
                try {
                    const u = localStorage.getItem('user');
                    return u ? JSON.parse(u) : null;
                } catch(e) {
                    return null;
                }
            })(),
            username: '',
            provider: {},
            branding: {
                name: 'Nexa',
                brand_color: '#8b5cf6',
                logo_path: null
            },
            providers: [],
            showStaffSelector: false,
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
            clientTimezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
            // Simulator additions
            magicBookingInProgress: false,
            magicBookingLogs: [],
            showAiChat: false,
            chatInput: '',
            aiProcessing: false,
            chatHistory: [
                { sender: 'ai', text: 'Hi! I am the Nexa AI booking coordinator. In this demo workspace, you can ask me to find slots and schedule appointments using natural language prompts.' }
            ],
            chatSuggestions: [
                { label: 'Book Jane Smith on Tuesday', text: 'Book Jane Smith next Tuesday' },
                { label: 'Schedule John Doe in the morning', text: 'Schedule John Doe next Monday morning' },
                { label: 'Book Sarah Johnson tomorrow', text: 'Book Sarah Johnson tomorrow' }
            ],
            utmParams: {
                utm_source: null,
                utm_medium: null,
                utm_campaign: null,
                utm_term: null,
                utm_content: null
            }
        };
    },
    computed: {
        brandColor() {
            return this.branding?.brand_color || '#8b5cf6';
        },
        brandRgb() {
            return hexToRgb(this.brandColor);
        },
        todayDateString() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        googleCalendarUrl() {
            if (!this.bookedAppointment) return '';
            const title = encodeURIComponent(this.bookedAppointment.title);
            const details = encodeURIComponent(this.bookedAppointment.note || `${this.branding.name} Discovery Sync Meeting`);
            const location = encodeURIComponent(this.bookedAppointment.meeting_link || 'Google Meet');
            
            const formatUTC = (dateStr) => {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
            };
            
            const dates = formatUTC(this.bookedAppointment.start_time) + '/' + formatUTC(this.bookedAppointment.end_time);
            return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dates}&details=${details}&location=${location}`;
        },
        isDemoMode() {
            return this.branding?.slug === 'demo' || this.branding?.is_demo || this.provider?.tenant?.is_demo || this.provider?.tenant?.slug === 'demo';
        }
    },
    created() {
        this.username = this.$route.params.username;
        this.initWorkspace();
        
        // Capture UTM parameters from URL query string
        const queries = this.$route.query;
        this.utmParams = {
            utm_source: queries.utm_source || null,
            utm_medium: queries.utm_medium || null,
            utm_campaign: queries.utm_campaign || null,
            utm_term: queries.utm_term || null,
            utm_content: queries.utm_content || null
        };
    },
    methods: {
        async initWorkspace() {
            this.loadingProvider = true;
            try {
                if (this.username) {
                    // Direct provider page
                    await this.fetchProvider(this.username);
                } else {
                    // Unified root workspace resolution
                    const response = await axios.get('/api/public/workspace');
                    this.branding = response.data.tenant;
                    this.providers = response.data.providers;

                    if (this.providers.length === 1) {
                        await this.fetchProvider(this.providers[0].email);
                    } else if (this.providers.length > 1) {
                        this.showStaffSelector = true;
                    } else {
                        this.providerError = 'This scheduling workspace currently has no active team members.';
                    }
                }
            } catch (err) {
                console.error("Workspace initialization failed", err);
                this.providerError = 'Could not resolve scheduling workspace details.';
            } finally {
                this.loadingProvider = false;
            }
        },
        async fetchProvider(identifier) {
            try {
                const response = await axios.get(`/api/public/booking/${identifier}`);
                this.provider = response.data;
                if (this.provider.tenant) {
                    this.branding = this.provider.tenant;
                }
                this.showStaffSelector = false;
            } catch (err) {
                console.error("Provider load failed", err);
                this.providerError = err.response?.data?.message || 'Could not load scheduling profile.';
            }
        },
        async selectProvider(p) {
            this.loadingProvider = true;
            try {
                await this.fetchProvider(p.email);
                this.selectedDate = '';
                this.slots = [];
                this.step = 1;
            } finally {
                this.loadingProvider = false;
            }
        },
        async selectTeamProvider() {
            this.loadingProvider = true;
            try {
                await this.fetchProvider('team');
                this.selectedDate = '';
                this.slots = [];
                this.step = 1;
            } finally {
                this.loadingProvider = false;
            }
        },
        goBackToStaff() {
            this.provider = {};
            this.showStaffSelector = true;
            this.selectedDate = '';
            this.slots = [];
            this.step = 1;
        },
        async fetchSlots() {
            if (!this.selectedDate) return;
            this.loadingSlots = true;
            this.slots = [];
            
            // Use resolved email/name lookup parameter
            const lookup = this.provider.email || this.username;
            try {
                const response = await axios.get(`/api/public/booking/${lookup}/slots`, {
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
                    notes: this.form.notes,
                    ...this.utmParams
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
            if (this.providers.length > 1) {
                this.provider = {};
                this.showStaffSelector = true;
            }
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
        },
        async triggerMagicBooking() {
            this.magicBookingInProgress = true;
            this.magicBookingLogs = [];
            
            try {
                if (!this.selectedDate) {
                    this.magicBookingLogs.push("Selecting date...");
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    const year = tomorrow.getFullYear();
                    const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
                    const day = String(tomorrow.getDate()).padStart(2, '0');
                    this.selectedDate = `${year}-${month}-${day}`;
                    
                    this.magicBookingLogs.push(`Date selected: ${this.selectedDate}. Fetching slots...`);
                    await this.fetchSlots();
                } else if (this.slots.length === 0) {
                    this.magicBookingLogs.push("Fetching slots...");
                    await this.fetchSlots();
                }
                
                const slot = this.slots.find(s => s.available);
                if (!slot) {
                    this.magicBookingLogs.push("No slots available on this date. Trying next day...");
                    const nextDay = new Date(this.selectedDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    const year = nextDay.getFullYear();
                    const month = String(nextDay.getMonth() + 1).padStart(2, '0');
                    const day = String(nextDay.getDate()).padStart(2, '0');
                    this.selectedDate = `${year}-${month}-${day}`;
                    await this.fetchSlots();
                    const slot2 = this.slots.find(s => s.available);
                    if (!slot2) {
                        alert("Could not find any available slot to simulate booking. Please select another date manually.");
                        this.magicBookingInProgress = false;
                        return;
                    }
                    this.selectedSlot = slot2;
                } else {
                    this.selectedSlot = slot;
                }
                
                this.magicBookingLogs.push(`Selected slot: ${this.selectedSlot.time_label}`);
                this.step = 2;
                
                const profiles = [
                    { name: 'Sarah Connor', email: 'sarah.connor@skynet.demo', company: 'Skynet Solutions', notes: 'Urgent meeting to discuss system automation scopes and neural net integrations.' },
                    { name: 'Bruce Wayne', email: 'bruce@wayne.demo', company: 'Wayne Enterprises', notes: 'Licensing discussion for custom defense tech, tactical armor specifications.' },
                    { name: 'Tony Stark', email: 'tony@stark.demo', company: 'Stark Industries', notes: 'Scoping B2B integration sync with Arc Reactor database endpoints.' },
                    { name: 'Miles Dyson', email: 'miles.dyson@cyberdyne.demo', company: 'Cyberdyne Systems', notes: 'Reviewing database scaling models for deep learning servers.' },
                    { name: 'Rachael Deckard', email: 'rachael@tyrell.demo', company: 'Tyrell Corp', notes: 'Discovery session on biotech supply chain tracking APIs.' }
                ];
                const profile = profiles[Math.floor(Math.random() * profiles.length)];
                
                this.magicBookingLogs.push(`Generating client profile: ${profile.name} (${profile.company})...`);
                this.form.name = profile.name;
                this.form.email = profile.email;
                this.form.notes = `[Simulation Company: ${profile.company}] ${profile.notes}`;
                
                await new Promise(resolve => setTimeout(resolve, 1200));
                
                this.magicBookingLogs.push("Submitting booking, syncing calendar, and generating CRM deal...");
                await this.submitBooking();
                
            } catch (err) {
                console.error("Magic booking failed:", err);
                alert("Simulator encountered an error: " + err.message);
            } finally {
                this.magicBookingInProgress = false;
                this.magicBookingLogs = [];
            }
        },
        executeAiSuggestion(opt) {
            this.chatInput = opt.text;
            this.submitChatMessage();
        },
        async submitChatMessage() {
            if (!this.chatInput.trim() || this.aiProcessing) return;
            const input = this.chatInput;
            this.chatInput = '';
            
            this.chatHistory.push({ sender: 'user', text: input });
            this.scrollToBottom();
            
            this.aiProcessing = true;
            const aiMsg = { sender: 'ai', text: '', loading: true };
            this.chatHistory.push(aiMsg);
            
            try {
                await new Promise(resolve => setTimeout(resolve, 1500));
                
                let foundStaff = null;
                const lowerInput = input.toLowerCase();
                
                if (lowerInput.includes('jane') || lowerInput.includes('smith')) {
                    foundStaff = this.providers.find(p => p.name.toLowerCase().includes('jane') || p.name.toLowerCase().includes('smith'));
                } else if (lowerInput.includes('john') || lowerInput.includes('doe')) {
                    foundStaff = this.providers.find(p => p.name.toLowerCase().includes('john') || p.name.toLowerCase().includes('doe'));
                } else if (lowerInput.includes('sarah') || lowerInput.includes('johnson')) {
                    foundStaff = this.providers.find(p => p.name.toLowerCase().includes('sarah') || p.name.toLowerCase().includes('johnson'));
                }
                
                let targetDate = new Date();
                let dateLabel = 'today';
                
                if (lowerInput.includes('tomorrow')) {
                    targetDate.setDate(targetDate.getDate() + 1);
                    dateLabel = 'tomorrow';
                } else if (lowerInput.includes('tuesday')) {
                    const currentDay = targetDate.getDay();
                    const daysToTuesday = (2 - currentDay + 7) % 7 || 7;
                    targetDate.setDate(targetDate.getDate() + daysToTuesday);
                    dateLabel = 'next Tuesday';
                } else if (lowerInput.includes('monday')) {
                    const currentDay = targetDate.getDay();
                    const daysToMonday = (1 - currentDay + 7) % 7 || 7;
                    targetDate.setDate(targetDate.getDate() + daysToMonday);
                    dateLabel = 'next Monday';
                } else {
                    targetDate.setDate(targetDate.getDate() + 1);
                    dateLabel = 'tomorrow';
                }
                
                const year = targetDate.getFullYear();
                const month = String(targetDate.getMonth() + 1).padStart(2, '0');
                const day = String(targetDate.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${day}`;
                
                aiMsg.loading = false;
                
                if (foundStaff) {
                    aiMsg.text = `Parsing query: Identified staff scheduler: "${foundStaff.name}". Attempting to view availability for ${dateLabel} (${dateStr})...`;
                    this.scrollToBottom();
                    
                    await this.selectProvider(foundStaff);
                    this.selectedDate = dateStr;
                    
                    aiMsg.text = `Selected "${foundStaff.name}". Fetching available timeslots on ${dateStr}...`;
                    this.scrollToBottom();
                    
                    await this.fetchSlots();
                    
                    const isMorning = lowerInput.includes('morning');
                    let slot = null;
                    if (isMorning) {
                        slot = this.slots.find(s => s.available && s.time_label.includes('AM'));
                    }
                    if (!slot) {
                        slot = this.slots.find(s => s.available);
                    }
                    
                    if (slot) {
                        this.selectedSlot = slot;
                        this.step = 2;
                        aiMsg.text = `Success! I parsed your request, switched to ${foundStaff.name}'s calendar, and auto-selected the slot at ${slot.time_label} for ${dateStr}. Please complete your details in the booking form!`;
                    } else {
                        aiMsg.text = `I found Dr. ${foundStaff.name}'s schedule, but there are no available slots left on ${dateStr}. Please try another date manually in the calendar wizard.`;
                    }
                } else {
                    if (!this.provider || !this.provider.id) {
                        aiMsg.text = "I couldn't identify which staff member you want to book with. Please select one from the team list first!";
                    } else {
                        this.selectedDate = dateStr;
                        aiMsg.text = `Searching availability for ${this.provider.name} on ${dateLabel} (${dateStr})...`;
                        this.scrollToBottom();
                        
                        await this.fetchSlots();
                        
                        const slot = this.slots.find(s => s.available);
                        if (slot) {
                            this.selectedSlot = slot;
                            this.step = 2;
                            aiMsg.text = `Success! I resolved your request and auto-selected ${slot.time_label} on ${dateStr} with ${this.provider.name}. Please complete the details form to finish booking!`;
                        } else {
                            aiMsg.text = `No slots are available with ${this.provider.name} on ${dateStr}. Please select another date manually.`;
                        }
                    }
                }
            } catch (err) {
                console.error("AI booking parsing failed:", err);
                aiMsg.loading = false;
                aiMsg.text = "Sorry, I ran into an error processing your query. Please pick your slots manually.";
            } finally {
                this.aiProcessing = false;
                this.scrollToBottom();
            }
        },
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.chatMessages;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                }
            });
        }
    }
}
</script>

<style scoped>
.hover-light {
    color: var(--text-secondary) !important;
}
.hover-light:hover {
    color: var(--text-primary) !important;
    text-shadow: 0 0 8px rgba(255, 255, 255, 0.2);
}
.min-h-screen {
    min-height: 100vh;
}
.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }

/* Dynamic Theme Color Overrides */
.btn-indigo {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: #ffffff !important;
}
.btn-indigo:hover {
    filter: brightness(0.9);
}
.text-indigo {
    color: var(--primary-color) !important;
}
.btn-outline-indigo {
    color: var(--primary-color) !important;
    border: 1px solid rgba(var(--primary-rgb), 0.4) !important;
    background-color: rgba(var(--primary-rgb), 0.05) !important;
}
.btn-outline-indigo:hover {
    background-color: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: #ffffff !important;
}

.hover-card:hover {
    border-color: var(--primary-color) !important;
    background-color: rgba(var(--primary-rgb), 0.04) !important;
    transform: translateY(-1px);
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

@keyframes slideIn {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.chat-messages::-webkit-scrollbar {
    width: 6px;
}
.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}
.chat-messages::-webkit-scrollbar-thumb {
    background: var(--border-dark);
    border-radius: 3px;
}
</style>
