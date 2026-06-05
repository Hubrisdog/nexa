<script setup>
import { ref, onMounted } from 'vue';
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

onMounted(() => {
    fetchAnalytics();
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

            <!-- Funnel and Day Heatmap Row -->
            <div class="row mb-4">
                <!-- Sales Funnel -->
                <div class="col-lg-7 mb-4">
                    <div class="card border-0 shadow-sm p-4 rounded-xl bg-white h-100">
                        <h5 class="font-weight-extrabold text-slate-800 mb-3"><i class="fas fa-filter text-indigo mr-1"></i> Sales Conversion Funnel</h5>
                        <p class="text-muted text-xs mb-4">Chronological flow from overall landing page traffic down to finalized Closed Won conversions.</p>
                        
                        <div class="d-flex flex-column gap-3 mt-2">
                            <div v-for="(step, index) in stats.funnel" :key="index" class="d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                                    <span class="font-weight-bold text-slate-700 text-sm">{{ step.stage }}</span>
                                    <div class="text-right">
                                        <span class="font-weight-bold text-slate-800 mr-2">{{ step.value }}</span>
                                        <span class="badge badge-light text-muted" style="font-size: 11px;">{{ step.percentage }}%</span>
                                    </div>
                                </div>
                                <div class="progress rounded-pill" style="height: 12px; background-color: #f1f5f9;">
                                    <div class="progress-bar rounded-pill" role="progressbar" 
                                         :style="{ 
                                             width: step.percentage + '%',
                                             background: 'linear-gradient(90deg, #4f46e5, #818cf8)'
                                         }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best Days Heatmap -->
                <div class="col-lg-5 mb-4">
                    <div class="card border-0 shadow-sm p-4 rounded-xl bg-white h-100">
                        <h5 class="font-weight-extrabold text-slate-800 mb-3"><i class="far fa-clock text-indigo mr-1"></i> Scheduling Day Density</h5>
                        <p class="text-muted text-xs mb-4">Day-of-week breakdown illustrating when clients book appointment intervals most frequently.</p>
                        
                        <div class="d-flex flex-column gap-3">
                            <div v-for="item in stats.heatmap" :key="item.day" class="d-flex align-items-center">
                                <div style="width: 90px; font-weight: 600; font-size: 13px; color: #475569;">
                                    {{ item.day }}
                                </div>
                                <div class="flex-grow-1 mx-3">
                                    <div class="progress rounded-pill" style="height: 10px; background-color: #f1f5f9;">
                                        <div class="progress-bar rounded-pill" role="progressbar" 
                                             :style="{ 
                                                 width: (item.count > 0 ? Math.max(10, (item.count / stats.summary.total_appointments) * 100) : 0) + '%',
                                                 background: getHeatmapBarColor(item.count)
                                             }"></div>
                                    </div>
                                </div>
                                <div style="width: 30px; text-align: right; font-weight: 700; font-size: 13px; color: #1e293b;">
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
                    <div class="card border-0 shadow-sm p-4 rounded-xl bg-white h-100">
                        <h5 class="font-weight-extrabold text-slate-800 mb-3"><i class="fas fa-users-cog text-indigo mr-1"></i> Staff Efficiency Matrix</h5>
                        <p class="text-muted text-xs mb-4">Real-time completion and cancellation logs compiled per active staff scheduler provider.</p>

                        <div class="table-responsive">
                            <table class="table table-hover border-0 align-middle mb-0">
                                <thead>
                                    <tr class="text-slate-500 border-bottom text-xs uppercase font-semibold" style="font-size: 11px;">
                                        <th class="border-0 px-2 py-3">Staff Provider</th>
                                        <th class="border-0 text-center py-3">Sessions</th>
                                        <th class="border-0 text-center py-3">Completed</th>
                                        <th class="border-0 text-center py-3">Cancelled</th>
                                        <th class="border-0 text-right pr-3 py-3">Completion Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="prov in stats.providerMatrix" :key="prov.name" style="font-size: 13px;">
                                        <td class="border-0 py-3 font-weight-bold text-slate-800">{{ prov.name }}</td>
                                        <td class="border-0 text-center py-3 font-weight-semibold text-slate-700">{{ prov.total }}</td>
                                        <td class="border-0 text-center py-3 text-emerald-600 font-weight-semibold">{{ prov.completed }}</td>
                                        <td class="border-0 text-center py-3 text-rose-600 font-weight-semibold">{{ prov.cancelled }}</td>
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
                    <div class="card border-0 shadow-sm p-4 rounded-xl bg-white h-100">
                        <h5 class="font-weight-extrabold text-slate-800 mb-3"><i class="fas fa-chart-pie text-indigo mr-1"></i> Stage Distribution</h5>
                        <p class="text-muted text-xs mb-4">Status representation of lead volumes divided across key CRM transaction gates.</p>

                        <div class="d-flex flex-column gap-2.5 mt-2">
                            <div v-for="stage in stats.dealStageDistribution" :key="stage.stage" class="d-flex justify-content-between align-items-center p-2.5 rounded-lg hover-bg" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                                <span class="font-weight-semibold text-slate-600" style="font-size: 13px;">{{ stage.stage }}</span>
                                <span class="badge badge-pill badge-indigo text-white px-2.5 py-1" style="font-weight: 700; font-size: 11px; background-color: #4f46e5;">
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
    background-color: #f1f5f9 !important;
}
.progress-bar {
    transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
