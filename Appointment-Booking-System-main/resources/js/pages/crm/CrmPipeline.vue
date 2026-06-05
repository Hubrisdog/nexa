<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const deals = ref([]);
const companies = ref([]);
const contacts = ref([]);
const activities = ref([]);

const selectedDeal = ref(null);
const showDealModal = ref(false);
const showCompanyModal = ref(false);
const showContactModal = ref(false);
const showActivityModal = ref(false);

const isSubmitting = ref(false);
const showActivityPane = ref(false);

// Filter Search
const searchQuery = ref('');

// Toast Alert
const toastMessage = ref('');
const showToast = ref(false);

const triggerToast = (msg) => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};

// Deal form
const dealForm = ref({
    title: '',
    company_id: '',
    contact_id: '',
    value: 0,
    stage: 'cold',
    score: null
});

// Company form
const companyForm = ref({
    name: '',
    industry: '',
    website: '',
    revenue: '',
    employee_count: ''
});

// Contact form
const contactForm = ref({
    company_id: '',
    name: '',
    position: '',
    email: '',
    phone: '',
    linkedin_url: ''
});

// Activity form
const activityForm = ref({
    company_id: '',
    contact_id: '',
    type: 'note',
    description: ''
});

// Pipeline Funnel Stages (Sales Funnel order)
const stages = [
    { key: 'cold', label: 'New Lead', color: '#6366f1' },
    { key: 'contacted', label: 'Qualified', color: '#0ea5e9' },
    { key: 'booked', label: 'Meeting Booked', color: '#a855f7' },
    { key: 'interested', label: 'Proposal Sent', color: '#f59e0b' },
    { key: 'closed_won', label: 'Closed Won', color: '#10b981' }
];

const fetchPipeline = async () => {
    try {
        const response = await axios.get('/api/crm/pipeline');
        deals.value = response.data;
    } catch (e) {
        console.error("Error fetching CRM pipeline:", e);
    }
};

const fetchCompanies = async () => {
    try {
        const response = await axios.get('/api/crm/companies');
        companies.value = response.data;
    } catch (e) {
        console.error("Error fetching companies:", e);
    }
};

const fetchContacts = async () => {
    try {
        const response = await axios.get('/api/crm/contacts');
        contacts.value = response.data;
    } catch (e) {
        console.error("Error fetching contacts:", e);
    }
};

const fetchActivities = async (deal) => {
    if (!deal) return;
    try {
        let url = '/api/crm/activities?';
        if (deal.company_id) url += `company_id=${deal.company_id}&`;
        if (deal.contact_id) url += `contact_id=${deal.contact_id}`;
        
        const response = await axios.get(url);
        activities.value = response.data;
    } catch (e) {
        console.error("Error fetching activities:", e);
    }
};

const openDealDetails = (deal) => {
    selectedDeal.value = deal;
    showActivityPane.value = true;
    fetchActivities(deal);
};

const closeActivityPane = () => {
    showActivityPane.value = false;
    selectedDeal.value = null;
};

// Drag and drop stage changes
const updateStage = async (deal, newStage) => {
    try {
        const response = await axios.put(`/api/crm/deals/${deal.id}/stage`, { stage: newStage });
        // Update local deal state
        const index = deals.value.findIndex(d => d.id === deal.id);
        if (index !== -1) {
            deals.value[index] = response.data;
        }
        if (selectedDeal.value && selectedDeal.value.id === deal.id) {
            selectedDeal.value = response.data;
            fetchActivities(response.data);
        }
    } catch (e) {
        console.error("Error updating deal stage:", e);
    }
};

const handleSaveDeal = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post('/api/crm/deals', dealForm.value);
        deals.value.push(response.data);
        showDealModal.value = false;
        dealForm.value = { title: '', company_id: '', contact_id: '', value: 0, stage: 'cold', score: null };
        triggerToast("New deal added and auto-scored successfully!");
    } catch (e) {
        console.error("Error saving deal:", e);
    } finally {
        isSubmitting.value = false;
    }
};

const handleSaveCompany = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post('/api/crm/companies', companyForm.value);
        companies.value.push(response.data);
        showCompanyModal.value = false;
        companyForm.value = { name: '', industry: '', website: '', revenue: '', employee_count: '' };
        triggerToast("Company profile saved.");
    } catch (e) {
        console.error("Error saving company:", e);
    } finally {
        isSubmitting.value = false;
    }
};

const handleSaveContact = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post('/api/crm/contacts', contactForm.value);
        contacts.value.push(response.data);
        showContactModal.value = false;
        contactForm.value = { company_id: '', name: '', position: '', email: '', phone: '', linkedin_url: '' };
        triggerToast("Contact profile saved.");
    } catch (e) {
        console.error("Error saving contact:", e);
    } finally {
        isSubmitting.value = false;
    }
};

const handleSaveActivity = async () => {
    isSubmitting.value = true;
    try {
        activityForm.value.company_id = selectedDeal.value.company_id;
        activityForm.value.contact_id = selectedDeal.value.contact_id;
        const response = await axios.post('/api/crm/activities', activityForm.value);
        activities.value.unshift(response.data);
        showActivityModal.value = false;
        activityForm.value = { company_id: '', contact_id: '', type: 'note', description: '' };
        triggerToast("Action logged successfully.");
    } catch (e) {
        console.error("Error logging activity:", e);
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeleteDeal = async (dealId) => {
    if (!confirm("Are you sure you want to delete this deal?")) return;
    try {
        await axios.delete(`/api/crm/deals/${dealId}`);
        deals.value = deals.value.filter(d => d.id !== dealId);
        if (selectedDeal.value && selectedDeal.value.id === dealId) {
            closeActivityPane();
        }
        triggerToast("Deal deleted.");
    } catch (e) {
        console.error("Error deleting deal:", e);
    }
};

const getInitials = (name) => {
    if (!name) return 'U';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const getScoreColor = (score) => {
    if (!score) return '#6b7280';
    if (score >= 80) return '#10b981';
    if (score >= 50) return '#f59e0b';
    return '#ef4444';
};

const getFilteredDeals = (stageKey) => {
    return deals.value.filter(deal => {
        const matchesStage = deal.stage === stageKey;
        const matchesSearch = deal.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (deal.company && deal.company.name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (deal.contact && deal.contact.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
        return matchesStage && matchesSearch;
    });
};

const getStageTotalValue = (stageKey) => {
    return getFilteredDeals(stageKey).reduce((acc, deal) => acc + parseFloat(deal.value || 0), 0);
};

// --- Kanban HTML5 Drag and Drop handlers ---
const handleDealDragStart = (e, deal) => {
    e.dataTransfer.setData('deal_id', deal.id);
    e.dataTransfer.effectAllowed = 'move';
};

const handleDealDragOver = (e) => {
    e.preventDefault();
};

const handleDealDrop = async (e, stageKey) => {
    e.preventDefault();
    const dealId = e.dataTransfer.getData('deal_id');
    if (!dealId) return;

    const deal = deals.value.find(d => d.id == dealId);
    if (deal && deal.stage !== stageKey) {
        await updateStage(deal, stageKey);
        triggerToast(`Moved deal "${deal.title}" to ${stageKey.replace('_', ' ').toUpperCase()}`);
    }
};
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

    <div class="content-header py-4" style="color: var(--text-primary);">
        <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h1 class="font-weight-extrabold mb-1 tracking-tight" style="font-size: 26px;">B2B CRM Pipeline</h1>
                <p class="text-muted mb-0 text-sm">Manage enterprise accounts, drag deal cards across sales stages, and auto-score leads.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                <button @click="showCompanyModal = true" class="btn btn-outline-secondary d-flex align-items-center" style="height: 40px; border-radius: 8px; font-weight: 500; font-size: 13.5px; gap: 6px;">
                    <i class="fas fa-building text-muted"></i> + Add Company
                </button>
                <button @click="showContactModal = true" class="btn btn-outline-secondary d-flex align-items-center" style="height: 40px; border-radius: 8px; font-weight: 500; font-size: 13.5px; gap: 6px;">
                    <i class="fas fa-address-book text-muted"></i> + Add Contact
                </button>
                <button @click="showDealModal = true" class="btn btn-gradient d-flex align-items-center px-4" style="height: 40px; border-radius: 8px; font-weight: 600; font-size: 13.5px; gap: 6px;">
                    <i class="fas fa-plus"></i> Add Deal
                </button>
            </div>
        </div>
    </div>

    <div class="content px-3 pb-5">
        <!-- Filter and Summary Bar -->
        <div class="mb-4 d-flex justify-content-between align-items-center p-3 border border-dark rounded-xl" style="background-color: var(--bg-dark-card); border-color: var(--border-dark) !important;">
            <div class="d-flex align-items-center" style="flex-grow: 1; max-width: 400px; position: relative;">
                <i class="fas fa-search text-muted" style="position: absolute; left: 14px;"></i>
                <input v-model="searchQuery" type="text" placeholder="Search deals, companies, contacts..." class="form-control pl-5 border-0 w-100" style="background-color: var(--bg-dark-hover) !important; color: var(--text-primary); border-radius: 8px; font-size: 14px; min-height: 40px; outline: none;" />
            </div>
            <div class="text-right d-none d-md-block">
                <span class="text-muted text-xs uppercase tracking-wider block font-semibold" style="font-size: 10px;">Total Pipeline Value</span>
                <span class="font-weight-extrabold text-lg text-white" style="font-size: 20px;">
                    ${{ deals.reduce((acc, d) => acc + parseFloat(d.value || 0), 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </span>
            </div>
        </div>

        <!-- Kanban View Wrapper -->
        <div class="d-flex gap-3 overflow-x-auto pb-4 align-items-start" style="min-height: calc(100vh - 220px);">
            
            <div 
                v-for="stage in stages" 
                :key="stage.key" 
                class="kanban-column flex-shrink-0" 
                style="min-width: 290px; width: 290px; background-color: rgba(17, 24, 39, 0.4); border: 1px solid var(--border-dark); border-radius: 12px; padding: 12px;"
                @dragover="handleDealDragOver"
                @drop="handleDealDrop($event, stage.key)"
            >
                <!-- Column Header -->
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <div>
                        <span class="font-weight-extrabold block text-white" style="font-size: 14px;">{{ stage.label }}</span>
                        <span class="text-xs text-muted block">{{ getFilteredDeals(stage.key).length }} Deals</span>
                    </div>
                    <span class="font-weight-bold text-indigo text-xs block">${{ getStageTotalValue(stage.key).toLocaleString() }}</span>
                </div>

                <!-- Cards Container -->
                <div class="d-flex flex-column gap-2" style="min-height: 400px;">
                    <div 
                        v-for="deal in getFilteredDeals(stage.key)" 
                        :key="deal.id" 
                        class="kanban-card" 
                        style="background: var(--bg-dark-card); border-radius: 10px; border: 1px solid var(--border-dark); padding: 14px; cursor: grab; transition: var(--transition-fast);" 
                        draggable="true"
                        @dragstart="handleDealDragStart($event, deal)"
                        @click="openDealDetails(deal)"
                    >
                        <div class="d-flex justify-content-between align-items-start gap-1">
                            <span class="font-weight-bold block text-white" style="font-size: 13.5px; line-height: 1.2;">{{ deal.title }}</span>
                            <!-- score badge -->
                            <span class="badge text-white px-2 py-0.5" :style="{ backgroundColor: getScoreColor(deal.score) }" style="font-size: 10px; border-radius: 12px; font-weight: 700;">
                                {{ deal.score ? deal.score + '%' : 'AI' }}
                            </span>
                        </div>

                        <div class="mt-2 text-xs text-secondary">
                            <span class="d-block" style="font-size: 12px; font-weight: 600; color: var(--text-primary);">
                                <i class="fas fa-building mr-1 text-muted"></i> {{ deal.company?.name || 'Individual Lead' }}
                            </span>
                            <span v-if="deal.contact" class="d-block mt-1 text-muted" style="font-size: 11px;">
                                <i class="far fa-user mr-1 text-muted"></i> {{ deal.contact.name }}
                            </span>
                        </div>

                        <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center" style="border-color: var(--border-dark) !important;">
                            <span class="font-weight-extrabold text-white" style="font-size: 14px;">${{ parseFloat(deal.value).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>
                            
                            <!-- Quick Move dropdown -->
                            <div class="btn-group" @click.stop>
                                <button class="btn btn-xs btn-outline-secondary dropdown-toggle border-0 py-1" data-toggle="dropdown" style="font-size: 10px; border-radius: 4px; padding: 2px 6px; color: var(--text-secondary);">
                                    Stage
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" style="border-radius: 8px; border: 1px solid var(--border-dark); background-color: var(--bg-dark-card);">
                                    <a v-for="targetStage in stages" :key="targetStage.key" class="dropdown-item py-1.5 px-3 text-secondary" style="font-size: 12px;" href="#" @click.prevent="updateStage(deal, targetStage.key)">
                                        {{ targetStage.label }}
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger py-1.5 px-3" style="font-size: 12px;" href="#" @click.prevent="handleDeleteDeal(deal.id)">
                                        <i class="far fa-trash-alt mr-1"></i> Delete Deal
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Clean Empty Columns State Illustration -->
                    <div v-if="getFilteredDeals(stage.key).length === 0" class="d-flex align-items-center justify-content-center border-dashed py-5 text-center text-muted" style="border: 2px dashed var(--border-dark); border-radius: 8px; min-height: 120px;">
                        <div class="px-2">
                            <i class="far fa-folder-open mb-1 text-muted d-block" style="font-size: 16px;"></i>
                            <span class="text-xs d-block">No active leads</span>
                            <span class="text-muted d-block mt-1" style="font-size: 9.5px; line-height: 1.2;">Drag a deal or click Add Deal to expand.</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Sliding Sidebar Drawer (Deal Activities & Details) -->
    <div class="activity-drawer shadow-lg" :class="{ 'open': showActivityPane }" style="background-color: var(--bg-dark-card); border-left: 1px solid var(--border-dark);">
        <div v-if="selectedDeal" class="h-100 d-flex flex-column" style="color: var(--text-primary);">
            <!-- Header -->
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--border-dark) !important; background-color: rgba(17, 24, 39, 0.4);">
                <div>
                    <span class="badge px-2 py-1 text-indigo font-semibold text-xs mb-1" style="background-color: rgba(99, 102, 241, 0.15); border-radius: 6px;">DEAL DETAILS</span>
                    <h4 class="font-weight-extrabold text-white mb-0" style="font-size: 18px;">{{ selectedDeal.title }}</h4>
                </div>
                <button @click="closeActivityPane" class="btn btn-xs text-muted border-0" style="font-size: 22px;">&times;</button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-grow-1 overflow-y-auto p-4">
                
                <!-- Deal Metrics -->
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="p-3 rounded-lg text-center border" style="background-color: var(--bg-dark-hover); border-color: var(--border-dark);">
                            <span class="text-muted text-xs block mb-1">Deal Value</span>
                            <span class="font-weight-extrabold text-indigo" style="font-size: 18px;">${{ parseFloat(selectedDeal.value).toLocaleString() }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-lg text-center border" style="background-color: var(--bg-dark-hover); border-color: var(--border-dark);">
                            <span class="text-muted text-xs block mb-1">Lead Score</span>
                            <span class="font-weight-extrabold" :style="{ color: getScoreColor(selectedDeal.score) }" style="font-size: 18px;">{{ selectedDeal.score || 0 }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Company Details Card -->
                <div class="card p-3 mb-4 rounded-xl" style="background-color: var(--bg-dark-card); border: 1px solid var(--border-dark);">
                    <div class="d-flex align-items-center mb-3" style="gap: 8px;">
                        <i class="fas fa-building text-indigo"></i>
                        <h6 class="mb-0 font-weight-bold text-white">Company Information</h6>
                    </div>
                    <div v-if="selectedDeal.company" class="text-sm">
                        <div class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Name</span>
                            <span class="font-weight-semibold text-white">{{ selectedDeal.company.name }}</span>
                        </div>
                        <div v-if="selectedDeal.company.industry" class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Industry</span>
                            <span>{{ selectedDeal.company.industry }}</span>
                        </div>
                        <div v-if="selectedDeal.company.website" class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Website</span>
                            <a :href="selectedDeal.company.website" target="_blank" class="text-indigo">{{ selectedDeal.company.website }}</a>
                        </div>
                        <div v-if="selectedDeal.company.revenue" class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Annual Revenue</span>
                            <span>{{ selectedDeal.company.revenue }}</span>
                        </div>
                        <div v-if="selectedDeal.company.employee_count" class="d-flex justify-content-between py-1.5">
                            <span class="text-muted text-xs">Employees</span>
                            <span>{{ selectedDeal.company.employee_count }}</span>
                        </div>
                    </div>
                    <div v-else class="text-muted text-xs text-center py-2">
                        No company assigned.
                    </div>
                </div>

                <!-- Contact Details Card -->
                <div class="card p-3 mb-4 rounded-xl" style="background-color: var(--bg-dark-card); border: 1px solid var(--border-dark);">
                    <div class="d-flex align-items-center mb-3" style="gap: 8px;">
                        <i class="fas fa-address-book text-indigo"></i>
                        <h6 class="mb-0 font-weight-bold text-white">Primary Contact</h6>
                    </div>
                    <div v-if="selectedDeal.contact" class="text-sm">
                        <div class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Name</span>
                            <span class="font-weight-semibold text-white">{{ selectedDeal.contact.name }}</span>
                        </div>
                        <div v-if="selectedDeal.contact.position" class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Position</span>
                            <span>{{ selectedDeal.contact.position }}</span>
                        </div>
                        <div v-if="selectedDeal.contact.email" class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Email</span>
                            <span>{{ selectedDeal.contact.email }}</span>
                        </div>
                        <div v-if="selectedDeal.contact.phone" class="d-flex justify-content-between py-1.5 border-bottom" style="border-color: var(--border-dark) !important;">
                            <span class="text-muted text-xs">Phone</span>
                            <span>{{ selectedDeal.contact.phone }}</span>
                        </div>
                        <div v-if="selectedDeal.contact.linkedin_url" class="d-flex justify-content-between py-1.5">
                            <span class="text-muted text-xs">LinkedIn</span>
                            <a :href="selectedDeal.contact.linkedin_url" target="_blank" class="text-indigo"><i class="fab fa-linkedin"></i> View Profile</a>
                        </div>
                    </div>
                    <div v-else class="text-muted text-xs text-center py-2">
                        No contact assigned.
                    </div>
                </div>

                <!-- Chronological Activity Timeline Feed -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="font-weight-bold text-white mb-0"><i class="fas fa-history mr-1"></i> Activity Logs</h6>
                    <button @click="showActivityModal = true" class="btn btn-xs btn-indigo" style="border-radius: 6px; font-weight: 700;">
                        + Log Action
                    </button>
                </div>

                <!-- Timeline Feed list -->
                <div class="activity-timeline pl-2" style="border-left: 2px solid var(--border-dark);">
                    <div v-for="act in activities" :key="act.id" class="timeline-item d-flex gap-3 mb-3 pl-3" style="position: relative;">
                        <!-- Custom left bullet indicator based on type -->
                        <div class="timeline-bullet" style="width: 10px; height: 10px; border-radius: 50%; position: absolute; left: -16px; top: 5px; background-color: var(--primary-color);"></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge px-2 py-0.5" style="background-color: var(--bg-dark-hover); color: var(--text-secondary); border-radius: 4px; font-size: 10px;">{{ act.type.toUpperCase() }}</span>
                                <span class="text-xs text-muted">{{ new Date(act.created_at).toLocaleDateString() }}</span>
                            </div>
                            <p class="text-sm mt-1.5 mb-1 text-white">{{ act.description }}</p>
                            <span class="text-xs text-muted">by {{ act.user?.name || 'SaaS Host' }}</span>
                        </div>
                    </div>

                    <div v-if="activities.length === 0" class="text-center py-4 text-muted text-xs">
                        No recorded communications. Click Log Action to add one.
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modals -->

    <!-- Add Deal Modal -->
    <div v-if="showDealModal" class="custom-modal-overlay" @click.self="showDealModal = false">
        <div class="custom-modal p-4" style="background-color: var(--bg-dark-card); border: 1px solid var(--border-dark); border-radius: 16px;">
            <h5 class="font-weight-extrabold mb-3 text-white">Create New CRM Deal</h5>
            <form @submit.prevent="handleSaveDeal">
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Deal Title</label>
                    <input v-model="dealForm.title" type="text" class="form-control form-control-modern w-100" placeholder="e.g. Enterprise License Package" required />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Company</label>
                    <select v-model="dealForm.company_id" class="form-control form-control-modern w-100">
                        <option value="">-- Select Company --</option>
                        <option v-for="comp in companies" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Contact Partner</label>
                    <select v-model="dealForm.contact_id" class="form-control form-control-modern w-100">
                        <option value="">-- Select Contact --</option>
                        <option v-for="cont in contacts" :key="cont.id" :value="cont.id">{{ cont.name }}</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Value ($)</label>
                            <input v-model="dealForm.value" type="number" step="0.01" class="form-control form-control-modern w-100" required />
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Lead Score (%)</label>
                            <input v-model="dealForm.score" type="number" min="0" max="100" class="form-control form-control-modern w-100" placeholder="AI auto-score" />
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Initial Stage</label>
                    <select v-model="dealForm.stage" class="form-control form-control-modern w-100" required>
                        <option v-for="stg in stages" :key="stg.key" :value="stg.key">{{ stg.label }}</option>
                    </select>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" @click="showDealModal = false" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-gradient px-4 py-2" :disabled="isSubmitting" style="border-radius: 8px;">
                        <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                        <span v-else>Save Deal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Company Modal -->
    <div v-if="showCompanyModal" class="custom-modal-overlay" @click.self="showCompanyModal = false">
        <div class="custom-modal p-4" style="background-color: var(--bg-dark-card); border: 1px solid var(--border-dark); border-radius: 16px;">
            <h5 class="font-weight-extrabold mb-3 text-white">Add B2B Company</h5>
            <form @submit.prevent="handleSaveCompany">
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Company Name</label>
                    <input v-model="companyForm.name" type="text" class="form-control form-control-modern w-100" placeholder="e.g. Acme Corporation" required />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Industry</label>
                    <input v-model="companyForm.industry" type="text" class="form-control form-control-modern w-100" placeholder="e.g. Technology / FinTech" />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Website URL</label>
                    <input v-model="companyForm.website" type="url" class="form-control form-control-modern w-100" placeholder="e.g. https://acme.com" />
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Annual Revenue</label>
                            <input v-model="companyForm.revenue" type="text" class="form-control form-control-modern w-100" placeholder="e.g. $12M" />
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group mb-3">
                            <label class="form-label text-xs uppercase tracking-wider text-muted">Employee Count</label>
                            <input v-model="companyForm.employee_count" type="number" class="form-control form-control-modern w-100" placeholder="e.g. 150" />
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" @click="showCompanyModal = false" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-gradient px-4 py-2" :disabled="isSubmitting" style="border-radius: 8px;">
                        <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                        <span v-else>Save Company</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Contact Modal -->
    <div v-if="showContactModal" class="custom-modal-overlay" @click.self="showContactModal = false">
        <div class="custom-modal p-4" style="background-color: var(--bg-dark-card); border: 1px solid var(--border-dark); border-radius: 16px;">
            <h5 class="font-weight-extrabold mb-3 text-white">Add Contact Person</h5>
            <form @submit.prevent="handleSaveContact">
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Full Name</label>
                    <input v-model="contactForm.name" type="text" class="form-control form-control-modern w-100" placeholder="e.g. Pepper Potts" required />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Company Affiliate</label>
                    <select v-model="contactForm.company_id" class="form-control form-control-modern w-100">
                        <option value="">-- None (Standalone) --</option>
                        <option v-for="comp in companies" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Position / Role</label>
                    <input v-model="contactForm.position" type="text" class="form-control form-control-modern w-100" placeholder="e.g. VP of Business Relations" />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Email Address</label>
                    <input v-model="contactForm.email" type="email" class="form-control form-control-modern w-100" placeholder="e.g. pepper@stark.com" />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Phone Number</label>
                    <input v-model="contactForm.phone" type="text" class="form-control form-control-modern w-100" placeholder="e.g. +1 (555) 019-9988" />
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">LinkedIn URL</label>
                    <input v-model="contactForm.linkedin_url" type="url" class="form-control form-control-modern w-100" placeholder="e.g. https://linkedin.com/in/pepper" />
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" @click="showContactModal = false" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-gradient px-4 py-2" :disabled="isSubmitting" style="border-radius: 8px;">
                        <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                        <span v-else>Save Contact</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log Activity Modal -->
    <div v-if="showActivityModal" class="custom-modal-overlay" @click.self="showActivityModal = false">
        <div class="custom-modal p-4" style="background-color: var(--bg-dark-card); border: 1px solid var(--border-dark); border-radius: 16px;">
            <h5 class="font-weight-extrabold mb-3 text-white">Log Interaction Activity</h5>
            <form @submit.prevent="handleSaveActivity">
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Activity Type</label>
                    <select v-model="activityForm.type" class="form-control form-control-modern w-100" required>
                        <option value="call">Phone Call</option>
                        <option value="email">Email Dispatch</option>
                        <option value="meeting">Video/In-Person Meeting</option>
                        <option value="note">General Internal Note</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label text-xs uppercase tracking-wider text-muted">Details / Interaction Summary</label>
                    <textarea v-model="activityForm.description" rows="4" class="form-control form-control-modern w-100" placeholder="e.g. Discussed pricing structures for onboarding package. Client requested updated proposal." required></textarea>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" @click="showActivityModal = false" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-gradient px-4 py-2" :disabled="isSubmitting" style="border-radius: 8px;">
                        <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                        <span v-else>Log Action</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.block { display: block; }
.rounded-xl {
    border-radius: 12px !important;
}
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--card-shadow) !important;
    border-color: #4b5563 !important;
}

/* Sliding Activity Drawer */
.activity-drawer {
    position: fixed;
    top: 0;
    right: -420px;
    width: 420px;
    height: 100vh;
    z-index: 1050;
    transition: right 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.4);
}
.activity-drawer.open {
    right: 0;
}

/* Custom Modal Overlay */
.custom-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(3, 7, 18, 0.6);
    backdrop-filter: blur(8px);
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
}
.custom-modal {
    width: 460px;
    max-width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
