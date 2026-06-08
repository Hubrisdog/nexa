<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const stats = ref({
    funnel: [],
    providerMatrix: [],
    heatmap: [],
    dealStageDistribution: [],
    summary: {
        total_revenue: 0,
        total_deals: 0,
        total_appointments: 0,
        average_score: 0
    }
});

const isLoading = ref(true);

const fetchAnalytics = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/analytics');
        stats.value = response.data;
        if (stats.value.summary) {
            stats.value.summary.total_revenue = response.data.summary.booked_revenue || 0;
        }
    } catch (e) {
        console.error("Error fetching analytics data:", e);
    } finally {
        isLoading.value = false;
    }
};

const getHeatmapBarColor = (count) => {
    if (count >= 10) return 'linear-gradient(135deg, #6366f1, #4f46e5)'; // Indigo
    if (count >= 5) return 'linear-gradient(135deg, #0ea5e9, #0284c7)'; // Blue/Sky
    return 'linear-gradient(135deg, #94a3b8, #64748b)'; // Slate
};

const getCompletionRateColor = (rate) => {
    if (rate >= 80) return 'success';
    if (rate >= 50) return 'warning';
    return 'danger';
};

// Traffic Simulator states
const isTrafficSimulating = ref(false);
const trafficLogs = ref([]);
const simIntervalId = ref(null);

const cities = ['New York', 'London', 'San Francisco', 'Tokyo', 'Berlin', 'Sydney', 'Paris', 'Toronto'];
const timeslots = ['9:30 AM', '10:00 AM', '11:30 AM', '1:00 PM', '2:30 PM', '3:30 PM', '4:00 PM'];
const names = ['Pepper Potts', 'Lucius Fox', 'Miles Dyson', 'Rachael Deckard', 'Sarah Connor', 'Bruce Wayne', 'Tony Stark'];

const toggleTrafficSimulator = () => {
    if (isTrafficSimulating.value) {
        isTrafficSimulating.value = false;
        if (simIntervalId.value) {
            clearInterval(simIntervalId.value);
            simIntervalId.value = null;
        }
        trafficLogs.value.push(`[System] Live Traffic Simulator stopped.`);
    } else {
        isTrafficSimulating.value = true;
        trafficLogs.value = [`[System] Live Traffic Simulator initialized. Listening on websocket endpoints...`];
        
        simIntervalId.value = setInterval(() => {
            simulateVisitorEvent();
        }, 1500);
    }
};

const simulateVisitorEvent = () => {
    const timestamp = new Date().toLocaleTimeString();
    const eventType = Math.floor(Math.random() * 5); // 0 to 4
    const city = cities[Math.floor(Math.random() * cities.length)];
    const slot = timeslots[Math.floor(Math.random() * timeslots.length)];
    const name = names[Math.floor(Math.random() * names.length)];
    const revenueVal = (Math.floor(Math.random() * 40) + 10) * 1000;
    
    if (stats.value && stats.value.funnel && stats.value.funnel.length >= 5) {
        if (eventType === 0) {
            stats.value.funnel[0].value++;
            trafficLogs.value.unshift(`[${timestamp}] 👤 New visitor from ${city} landed on public booking page.`);
        } else if (eventType === 1) {
            stats.value.funnel[0].value++;
            stats.value.funnel[2].value++;
            trafficLogs.value.unshift(`[${timestamp}] 📅 Visitor from ${city} clicked available timeslot (${slot}).`);
        } else if (eventType === 2) {
            stats.value.funnel[0].value++;
            stats.value.funnel[1].value++;
            stats.value.funnel[2].value++;
            trafficLogs.value.unshift(`[${timestamp}] 📝 Lead generated: Contact form submitted by company representative.`);
        } else if (eventType === 3) {
            stats.value.funnel[0].value++;
            stats.value.funnel[1].value++;
            stats.value.funnel[2].value++;
            stats.value.funnel[3].value++;
            stats.value.summary.total_appointments++;
            trafficLogs.value.unshift(`[${timestamp}] 🚀 Sync success: Discovery meeting completed and logged in CRM database.`);
        } else if (eventType === 4) {
            stats.value.funnel[0].value++;
            stats.value.funnel[1].value++;
            stats.value.funnel[2].value++;
            stats.value.funnel[3].value++;
            stats.value.funnel[4].value++;
            stats.value.summary.total_deals++;
            stats.value.summary.total_revenue += revenueVal;
            trafficLogs.value.unshift(`[${timestamp}] 💰 Deal Closed Won! Opportunity with ${name} finalized (+$${revenueVal.toLocaleString()}).`);
        }
        
        const visits = stats.value.funnel[0].value;
        if (visits > 0) {
            stats.value.funnel[1].percentage = parseFloat(((stats.value.funnel[1].value / visits) * 100).toFixed(1));
            stats.value.funnel[2].percentage = parseFloat(((stats.value.funnel[2].value / visits) * 100).toFixed(1));
            stats.value.funnel[3].percentage = parseFloat(((stats.value.funnel[3].value / visits) * 100).toFixed(1));
            stats.value.funnel[4].percentage = parseFloat(((stats.value.funnel[4].value / visits) * 100).toFixed(1));
        }
    }
    
    if (trafficLogs.value.length > 20) {
        trafficLogs.value.pop();
    }
};

onMounted(() => {
    fetchAnalytics();
});

onUnmounted(() => {
    if (simIntervalId.value) {
        clearInterval(simIntervalId.value);
    }
});
</script>

<template>
    <div class="content-header py-4">
        <div class="container-fluid">
            <h1 class="font-weight-extrabold mb-1 tracking-tight" style="font-size: 26px;">Performance Analytics</h1>
            <p class="text-muted mb-0 text-sm">Monitor lead acquisition funnels, conversion rates, and provider efficiency matrix metrics.</p>
        </div>
    </div>

    <div class="content px-3">
        <div v-if="isLoading" class="d-flex justify-content-center align-items-center py-5" style="min-height: 300px;">
            <div class="spinner-border text-indigo" role="status">
                <span class="sr-only">Loading metrics...</span>
            </div>
        </div>

        <div v-else>
            <!-- Premium Summary Grid -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm p-4 rounded-xl text-white" style="background: linear-gradient(135deg, #4f46e5, #3730a3);">
                        <span class="text-xs uppercase tracking-wider block font-semibold text-indigo-100">Closed Revenue</span>
                        <h3 class="font-weight-extrabold mt-2 mb-0" style="font-size: 28px;">
                            ${{ parseFloat(stats.summary.total_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </h3>
                        <span class="text-xs text-indigo-200 mt-2 block"><i class="fas fa-arrow-up mr-1"></i> From closed deals</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm p-4 rounded-xl text-white" style="background: linear-gradient(135deg, #0ea5e9, #0369a1);">
                        <span class="text-xs uppercase tracking-wider block font-semibold text-sky-100">Total B2B Deals</span>
                        <h3 class="font-weight-extrabold mt-2 mb-0" style="font-size: 28px;">
                            {{ stats.summary.total_deals }}
                        </h3>
                        <span class="text-xs text-sky-200 mt-2 block"><i class="fas fa-briefcase mr-1"></i> Under active pipeline</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm p-4 rounded-xl text-white" style="background: linear-gradient(135deg, #a855f7, #7e22ce);">
                        <span class="text-xs uppercase tracking-wider block font-semibold text-purple-100">Booked Sessions</span>
                        <h3 class="font-weight-extrabold mt-2 mb-0" style="font-size: 28px;">
                            {{ stats.summary.total_appointments }}
                        </h3>
                        <span class="text-xs text-purple-200 mt-2 block"><i class="far fa-calendar-check mr-1"></i> Appointments scheduled</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-0 shadow-sm p-4 rounded-xl text-white" style="background: linear-gradient(135deg, #10b981, #065f46);">
                        <span class="text-xs uppercase tracking-wider block font-semibold text-emerald-100">Avg Lead Score</span>
                        <h3 class="font-weight-extrabold mt-2 mb-0" style="font-size: 28px;">
                            {{ stats.summary.average_score }}%
                        </h3>
                        <span class="text-xs text-emerald-200 mt-2 block"><i class="fas fa-bolt mr-1"></i> Qualification health</span>
                    </div>
                </div>
            </div>

            <!-- Live Traffic Simulator Console Card -->
            <div class="card glass-card border-0 p-4 mb-4" style="background: var(--bg-dark-card); border: 1px solid var(--border-dark) !important; border-radius: 16px;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="text-left">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge px-2.5 py-1" style="background: rgba(16, 185, 129, 0.15); color: var(--accent-color); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 6px; font-weight: 700; font-size: 11px;">SIMULATION ENGINE</span>
                            <h5 class="font-weight-extrabold m-0 text-white" style="font-size: 16px;">Web Traffic Simulator Console</h5>
                        </div>
                        <p class="text-muted text-xs mb-0 mt-1">Inject real-time booking visits and funnel events to observe live conversion statistics adjustments.</p>
                    </div>
                    <button @click="toggleTrafficSimulator" class="btn btn-sm d-flex align-items-center gap-2 text-white" :class="isTrafficSimulating ? 'btn-danger' : 'btn-indigo'" style="border-radius: 10px; font-weight: 700; border: 0; padding: 10px 18px;">
                        <i class="fas" :class="isTrafficSimulating ? 'fa-stop-circle animate-pulse' : 'fa-play-circle'"></i>
                        <span>{{ isTrafficSimulating ? 'Stop Traffic Simulator' : 'Start Live Traffic Simulator' }}</span>
                    </button>
                </div>

                <!-- Simulation Output console -->
                <div v-if="isTrafficSimulating || trafficLogs.length > 0" class="mt-3">
                    <div class="p-3" style="background: #090d16; border-radius: 10px; border: 1px solid var(--border-dark); height: 110px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 11.5px; line-height: 1.5; color: #34d399;">
                        <div v-for="(log, idx) in trafficLogs" :key="idx" class="terminal-line">
                            <span class="text-muted mr-1">&gt;</span> {{ log }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Funnel and Day Heatmap Row -->
            <div class="row mb-4">
                <!-- Sales Funnel -->
                <div class="col-lg-7 mb-4">
                    <div class="card border-0 shadow-sm p-4 rounded-xl h-100">
                        <h5 class="font-weight-extrabold mb-3" style="color: var(--text-primary);"><i class="fas fa-filter text-indigo mr-1"></i> Sales Conversion Funnel</h5>
                        <p class="text-muted text-xs mb-4">Chronological flow from overall landing page traffic down to finalized Closed Won conversions.</p>
                        
                        <div class="d-flex flex-column align-items-center gap-3 mt-4" style="position: relative;">
                            <div v-for="(step, index) in stats.funnel" :key="index" class="d-flex flex-column text-center p-3 funnel-step-card hover-grow" 
                                 :style="{ 
                                     width: (100 - index * 12) + '%',
                                     maxWidth: '600px',
                                     background: 'rgba(99, 102, 241, ' + (0.04 + (5 - index) * 0.03) + ')',
                                     border: '1px solid rgba(99, 102, 241, ' + (0.1 + (5 - index) * 0.05) + ')',
                                     borderRadius: '12px',
                                     boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                                     transition: 'all 0.3s ease'
                                 }">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-xs font-weight-bold text-muted" style="width: 20px;">#{{ index + 1 }}</span>
                                        <span class="font-weight-extrabold text-sm text-white">{{ step.stage }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="font-weight-extrabold text-md text-indigo">{{ step.value }}</span>
                                        <span class="badge text-white px-2 py-1" style="font-size: 11px; background: var(--primary-gradient); font-weight: 700; border-radius: 6px;">{{ step.percentage }}%</span>
                                    </div>
                                </div>
                                
                                <div class="progress mt-2" style="height: 4px; background-color: rgba(255, 255, 255, 0.05); border-radius: 2px;">
                                    <div class="progress-bar" role="progressbar" 
                                         :style="{ 
                                             width: step.percentage + '%',
                                             background: 'var(--primary-gradient)'
                                         }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best Days Heatmap -->
                <div class="col-lg-5 mb-4">
                    <div class="card border-0 shadow-sm p-4 rounded-xl h-100">
                        <h5 class="font-weight-extrabold mb-3" style="color: var(--text-primary);"><i class="far fa-clock text-indigo mr-1"></i> Scheduling Day Density</h5>
                        <p class="text-muted text-xs mb-4">Day-of-week breakdown illustrating when clients book appointment intervals most frequently.</p>
                        
                        <div class="d-flex flex-column gap-3">
                            <div v-for="item in stats.heatmap" :key="item.day" class="d-flex align-items-center">
                                <div style="width: 90px; font-weight: 600; font-size: 13px; color: var(--text-secondary);">
                                    {{ item.day }}
                                </div>
                                <div class="flex-grow-1 mx-3">
                                    <div class="progress rounded-pill" style="height: 10px; background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark);">
                                        <div class="progress-bar rounded-pill" role="progressbar" 
                                             :style="{ 
                                                 width: (item.count > 0 ? Math.max(10, (item.count / stats.summary.total_appointments) * 100) : 0) + '%',
                                                 background: getHeatmapBarColor(item.count)
                                             }"></div>
                                    </div>
                                </div>
                                <div style="width: 30px; text-align: right; font-weight: 700; font-size: 13px; color: var(--text-primary);">
                                    {{ item.count }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Provider Matrix and Deal Distribution Row -->
            <div class="row">
                <!-- Provider Matrix -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm p-4 rounded-xl h-100">
                        <h5 class="font-weight-extrabold mb-3" style="color: var(--text-primary);"><i class="fas fa-users-cog text-indigo mr-1"></i> Staff Efficiency Matrix</h5>
                        <p class="text-muted text-xs mb-4">Real-time completion and cancellation logs compiled per active staff scheduler provider.</p>

                        <div class="table-responsive">
                            <table class="table table-hover border-0 align-middle mb-0">
                                <thead>
                                    <tr class="border-bottom text-xs uppercase font-semibold" style="font-size: 11px; color: var(--text-secondary); border-color: var(--border-dark) !important;">
                                        <th class="border-0 px-2 py-3">Staff Provider</th>
                                        <th class="border-0 text-center py-3">Sessions</th>
                                        <th class="border-0 text-center py-3">Completed</th>
                                        <th class="border-0 text-center py-3">Cancelled</th>
                                        <th class="border-0 text-right pr-3 py-3">Completion Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="prov in stats.providerMatrix" :key="prov.name" style="font-size: 13px;">
                                        <td class="border-0 py-3 font-weight-bold" style="color: var(--text-primary);">{{ prov.name }}</td>
                                        <td class="border-0 text-center py-3 font-weight-semibold" style="color: var(--text-secondary);">{{ prov.total }}</td>
                                        <td class="border-0 text-center py-3 font-weight-semibold" style="color: var(--accent-color);">{{ prov.completed }}</td>
                                        <td class="border-0 text-center py-3 font-weight-semibold" style="color: #f87171;">{{ prov.cancelled }}</td>
                                        <td class="border-0 text-right pr-3 py-3">
                                            <span class="badge px-2 py-1" :class="'badge-' + getCompletionRateColor(prov.completion_rate)" style="border-radius: 6px; font-size: 11px;">
                                                {{ prov.completion_rate }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Deal Stage Distribution -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm p-4 rounded-xl h-100">
                        <h5 class="font-weight-extrabold mb-3" style="color: var(--text-primary);"><i class="fas fa-chart-pie text-indigo mr-1"></i> Stage Distribution</h5>
                        <p class="text-muted text-xs mb-4">Status representation of lead volumes divided across key CRM transaction gates.</p>

                        <div class="d-flex flex-column gap-2.5 mt-2">
                            <div v-for="stage in stats.dealStageDistribution" :key="stage.stage" class="d-flex justify-content-between align-items-center p-2.5 rounded-lg hover-bg" style="background-color: var(--bg-dark-hover); border: 1px solid var(--border-dark);">
                                <span class="font-weight-semibold" style="font-size: 13px; color: var(--text-primary);">{{ stage.stage }}</span>
                                <span class="badge badge-pill badge-indigo text-white px-2.5 py-1" style="font-weight: 700; font-size: 11px; background: var(--primary-gradient);">
                                    {{ stage.count }} Deals
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.text-indigo-100 { color: #e0e7ff; }
.text-indigo-200 { color: #c7d2fe; }
.text-sky-100 { color: #e0f2fe; }
.text-sky-200 { color: #bae6fd; }
.text-purple-100 { color: #f3e8ff; }
.text-purple-200 { color: #e9d5ff; }
.text-emerald-100 { color: #d1fae5; }
.text-emerald-200 { color: #a7f3d0; }
.rounded-xl {
    border-radius: 12px !important;
}
.text-indigo {
    color: #4f46e5;
}
.border-dashed {
    border: 2px dashed #cbd5e1;
}
.hover-bg:hover {
    background-color: var(--border-dark) !important;
}
.progress-bar {
    transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.funnel-step-card {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.funnel-step-card:hover {
    transform: scale(1.025);
    border-color: rgba(99, 102, 241, 0.45) !important;
    background: rgba(99, 102, 241, 0.12) !important;
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.animate-pulse {
    animation: pulse 1.5s infinite;
}
.terminal-line {
    margin-bottom: 4px;
}
</style>
