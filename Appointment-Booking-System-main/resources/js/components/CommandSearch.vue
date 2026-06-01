<template>
    <div v-if="isOpen" class="custom-modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1099; background: rgba(3, 7, 18, 0.6); backdrop-filter: blur(8px);" @click="closeModal">
        <div class="search-modal-container" @click.stop>
            <div class="search-modal-input-wrapper">
                <i class="fas fa-search text-muted" style="font-size: 18px;"></i>
                <input 
                    ref="searchInput"
                    v-model="query" 
                    type="text" 
                    class="search-modal-input" 
                    placeholder="Search clients, staff, deals, companies, contacts..." 
                    @input="handleInput"
                    @keydown.down.prevent="moveDown"
                    @keydown.up.prevent="moveUp"
                    @keydown.enter.prevent="selectItem"
                    @keydown.esc="closeModal"
                />
                <button class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px; border-radius: 6px;" @click="closeModal">ESC</button>
            </div>
            
            <div v-if="loading" class="text-center py-4 text-muted">
                <i class="fas fa-spinner fa-spin mr-2"></i> Searching...
            </div>
            
            <div v-else-if="hasResults" class="search-results-list">
                <!-- Appointments Results -->
                <div v-if="results.appointments && results.appointments.length">
                    <div class="search-result-category">Appointments</div>
                    <div 
                        v-for="(item, index) in results.appointments" 
                        :key="'appt-' + item.id" 
                        class="search-result-item"
                        :class="{ 'selected': isSelected('appointments', index) }"
                        @mouseenter="setSelectedIndex('appointments', index)"
                        @click="navigateTo('/admin/appointment', item)"
                    >
                        <div>
                            <span class="font-weight-bold" style="color: var(--text-primary);">{{ item.title }}</span>
                            <span class="text-xs text-muted d-block">
                                {{ formatDate(item.start_time) }} - Client: {{ item.client?.name || 'N/A' }} (Staff: {{ item.staff?.name || 'N/A' }})
                            </span>
                        </div>
                        <span class="badge" :class="getStatusBadgeClass(item.status)">{{ item.status }}</span>
                    </div>
                </div>

                <!-- Deals Results -->
                <div v-if="results.deals && results.deals.length">
                    <div class="search-result-category">CRM Deals</div>
                    <div 
                        v-for="(item, index) in results.deals" 
                        :key="'deal-' + item.id" 
                        class="search-result-item"
                        :class="{ 'selected': isSelected('deals', index) }"
                        @mouseenter="setSelectedIndex('deals', index)"
                        @click="navigateTo('/admin/crm', item)"
                    >
                        <div>
                            <span class="font-weight-bold" style="color: var(--text-primary);">{{ item.title }}</span>
                            <span class="text-xs text-muted d-block">Stage: {{ item.stage }}</span>
                        </div>
                        <span class="font-weight-bold text-indigo" style="font-size: 13px;">${{ formatNumber(item.value) }}</span>
                    </div>
                </div>

                <!-- Companies Results -->
                <div v-if="results.companies && results.companies.length">
                    <div class="search-result-category">Companies</div>
                    <div 
                        v-for="(item, index) in results.companies" 
                        :key="'company-' + item.id" 
                        class="search-result-item"
                        :class="{ 'selected': isSelected('companies', index) }"
                        @mouseenter="setSelectedIndex('companies', index)"
                        @click="navigateTo('/admin/crm', item)"
                    >
                        <div>
                            <span class="font-weight-bold" style="color: var(--text-primary);">{{ item.name }}</span>
                            <span class="text-xs text-muted d-block">{{ item.industry || 'No Industry' }}</span>
                        </div>
                        <span class="text-xs text-muted">{{ item.website || '' }}</span>
                    </div>
                </div>

                <!-- Contacts Results -->
                <div v-if="results.contacts && results.contacts.length">
                    <div class="search-result-category">CRM Contacts</div>
                    <div 
                        v-for="(item, index) in results.contacts" 
                        :key="'contact-' + item.id" 
                        class="search-result-item"
                        :class="{ 'selected': isSelected('contacts', index) }"
                        @mouseenter="setSelectedIndex('contacts', index)"
                        @click="navigateTo('/admin/crm', item)"
                    >
                        <div>
                            <span class="font-weight-bold" style="color: var(--text-primary);">{{ item.name }}</span>
                            <span class="text-xs text-muted d-block">{{ item.position || 'Contact' }}</span>
                        </div>
                        <span class="text-xs text-muted">{{ item.email }}</span>
                    </div>
                </div>

                <!-- Users Results -->
                <div v-if="results.users && results.users.length">
                    <div class="search-result-category">Users (Clients & Staff)</div>
                    <div 
                        v-for="(item, index) in results.users" 
                        :key="'user-' + item.id" 
                        class="search-result-item"
                        :class="{ 'selected': isSelected('users', index) }"
                        @mouseenter="setSelectedIndex('users', index)"
                        @click="navigateTo('/admin/users', item)"
                    >
                        <div>
                            <span class="font-weight-bold" style="color: var(--text-primary);">{{ item.name }}</span>
                            <span class="text-xs text-muted d-block">{{ item.email }}</span>
                        </div>
                        <span class="badge badge-info text-capitalize">{{ item.role }}</span>
                    </div>
                </div>
            </div>
            
            <div v-else-if="query.length >= 2" class="text-center py-4 text-muted">
                No results found for "{{ query }}"
            </div>
            
            <div v-else class="text-center py-4 text-muted" style="font-size: 13px;">
                Type at least 2 characters to search...
                <div class="mt-2 text-xs">
                    Tip: Use <kbd class="bg-dark text-muted">↑</kbd> <kbd class="bg-dark text-muted">↓</kbd> to navigate, and <kbd class="bg-dark text-muted">Enter</kbd> to select.
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'CommandSearch',
    data() {
        return {
            isOpen: false,
            query: '',
            loading: false,
            results: {
                users: [],
                companies: [],
                contacts: [],
                deals: [],
                appointments: []
            },
            selectedCategory: null,
            selectedIndex: 0,
            debounceTimer: null
        };
    },
    computed: {
        hasResults() {
            return Object.values(this.results).some(arr => arr && arr.length > 0);
        },
        flattenedResults() {
            const list = [];
            const categories = ['appointments', 'deals', 'companies', 'contacts', 'users'];
            categories.forEach(cat => {
                if (this.results[cat]) {
                    this.results[cat].forEach((item, idx) => {
                        list.push({ category: cat, index: idx, item });
                    });
                }
            });
            return list;
        }
    },
    mounted() {
        window.addEventListener('keydown', this.handleGlobalKeyDown);
    },
    beforeUnmount() {
        window.removeEventListener('keydown', this.handleGlobalKeyDown);
    },
    methods: {
        openModal() {
            this.isOpen = true;
            this.query = '';
            this.clearResults();
            this.$nextTick(() => {
                if (this.$refs.searchInput) {
                    this.$refs.searchInput.focus();
                }
            });
        },
        closeModal() {
            this.isOpen = false;
        },
        clearResults() {
            this.results = {
                users: [],
                companies: [],
                contacts: [],
                deals: [],
                appointments: []
            };
            this.selectedCategory = null;
            this.selectedIndex = 0;
        },
        handleInput() {
            clearTimeout(this.debounceTimer);
            if (this.query.length < 2) {
                this.clearResults();
                return;
            }
            
            this.debounceTimer = setTimeout(() => {
                this.search();
            }, 300);
        },
        search() {
            this.loading = true;
            axios.get('/api/search', { params: { q: this.query } })
                .then(res => {
                    this.results = res.data;
                    // Reset selection to first item
                    const flat = this.flattenedResults;
                    if (flat.length > 0) {
                        this.selectedCategory = flat[0].category;
                        this.selectedIndex = flat[0].index;
                    } else {
                        this.selectedCategory = null;
                        this.selectedIndex = 0;
                    }
                })
                .catch(err => {
                    console.error('Search error', err);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        handleGlobalKeyDown(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (this.isOpen) {
                    this.closeModal();
                } else {
                    this.openModal();
                }
            }
        },
        isSelected(category, index) {
            return this.selectedCategory === category && this.selectedIndex === index;
        },
        setSelectedIndex(category, index) {
            this.selectedCategory = category;
            this.selectedIndex = index;
        },
        moveDown() {
            const flat = this.flattenedResults;
            if (flat.length === 0) return;
            
            const currentFlatIndex = flat.findIndex(
                f => f.category === this.selectedCategory && f.index === this.selectedIndex
            );
            
            const nextFlatIndex = (currentFlatIndex + 1) % flat.length;
            const nextItem = flat[nextFlatIndex];
            this.selectedCategory = nextItem.category;
            this.selectedIndex = nextItem.index;
        },
        moveUp() {
            const flat = this.flattenedResults;
            if (flat.length === 0) return;
            
            const currentFlatIndex = flat.findIndex(
                f => f.category === this.selectedCategory && f.index === this.selectedIndex
            );
            
            const prevFlatIndex = (currentFlatIndex - 1 + flat.length) % flat.length;
            const prevItem = flat[prevFlatIndex];
            this.selectedCategory = prevItem.category;
            this.selectedIndex = prevItem.index;
        },
        selectItem() {
            const flat = this.flattenedResults;
            if (flat.length === 0) return;
            
            const matched = flat.find(
                f => f.category === this.selectedCategory && f.index === this.selectedIndex
            );
            
            if (matched) {
                let path = '/admin/dashboard';
                if (matched.category === 'users') path = '/admin/users';
                else if (matched.category === 'appointments') path = '/admin/appointment';
                else if (['deals', 'companies', 'contacts'].includes(matched.category)) path = '/admin/crm';
                
                this.navigateTo(path, matched.item);
            }
        },
        navigateTo(path, item) {
            this.closeModal();
            this.$router.push(path);
        },
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        },
        formatNumber(val) {
            if (!val) return '0.00';
            return parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        getStatusBadgeClass(status) {
            switch(status) {
                case 'scheduled': return 'badge-primary';
                case 'confirmed': return 'badge-purple bg-purple-light';
                case 'completed': return 'badge-success';
                case 'cancelled': return 'badge-danger';
                default: return 'badge-secondary';
            }
        }
    }
};
</script>
