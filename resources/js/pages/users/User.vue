<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const users = ref([]);
const pagination = ref({});
const search = ref('');
const roleFilter = ref('all');
const currentPage = ref(1);

const isDrawerOpen = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const form = ref({
    name: '',
    email: '',
    phone: '',
    role: 'client',
    password: ''
});

const errors = ref({});
const isSubmitting = ref(false);

const fetchUsers = async (page = 1) => {
    currentPage.value = page;
    let url = `/api/users?page=${page}`;
    if (search.value) url += `&search=${encodeURIComponent(search.value)}`;
    if (roleFilter.value !== 'all') url += `&role=${roleFilter.value}`;
    
    try {
        const response = await axios.get(url);
        users.value = response.data.data;
        pagination.value = response.data;
    } catch (error) {
        console.error("Error fetching users:", error);
    }
};

const handleSearch = () => {
    fetchUsers(1);
};

const handleRoleFilter = () => {
    fetchUsers(1);
};

const openCreateDrawer = () => {
    isEditing.value = false;
    editingUserId.value = null;
    form.value = {
        name: '',
        email: '',
        phone: '',
        role: 'client',
        password: ''
    };
    errors.value = {};
    isDrawerOpen.value = true;
};

const openEditDrawer = (user) => {
    isEditing.value = true;
    editingUserId.value = user.id;
    form.value = {
        name: user.name,
        email: user.email,
        phone: user.phone || '',
        role: user.role,
        password: ''
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
            await axios.put(`/api/users/${editingUserId.value}`, form.value);
        } else {
            await axios.post('/api/users', form.value);
        }
        closeDrawer();
        fetchUsers(currentPage.value);
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error("Error saving user:", error);
        }
    } finally {
        isSubmitting.value = false;
    }
};

const deleteUser = async (user) => {
    if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        try {
            await axios.delete(`/api/users/${user.id}`);
            fetchUsers(currentPage.value);
        } catch (error) {
            if (error.response && error.response.status === 403) {
                alert(error.response.data.message);
            } else {
                console.error("Error deleting user:", error);
            }
        }
    }
};

const getInitials = (name) => {
    if (!name) return 'U';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
};

const getAvatarColorClass = (id) => {
    const classes = ['bg-indigo', 'bg-purple', 'bg-teal', 'bg-pink', 'bg-orange', 'bg-blue'];
    return classes[(id || 0) % classes.length];
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <div class="content-header py-4">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="font-weight-extrabold mb-1 tracking-tight" style="font-size: 26px;">User Manager</h1>
                <p class="text-muted mb-0 text-sm">Add, update, and manage accounts for clinic administrators, staff, and clients.</p>
            </div>
            <button @click="openCreateDrawer" class="btn btn-gradient px-4 py-2" style="height: 44px; display: inline-flex; align-items: center;">
                <i class="fas fa-user-plus mr-2"></i> Add User
            </button>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Filter Grid -->
            <div class="row mb-4">
                <div class="col-md-8 mb-3 mb-md-0">
                    <div class="input-group-custom bg-white shadow-sm" style="height: 48px;">
                        <span class="input-icon"><i class="fas fa-search"></i></span>
                        <input 
                            v-model="search" 
                            @input="handleSearch" 
                            type="text" 
                            class="form-control-custom" 
                            placeholder="Search accounts by name, email, or telephone..."
                        >
                    </div>
                </div>
                <div class="col-md-4">
                    <select v-model="roleFilter" @change="handleRoleFilter" class="form-control shadow-sm" style="height: 48px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px; font-weight: 500; font-size: 14.5px;">
                        <option value="all">All System Roles</option>
                        <option value="admin">Administrators</option>
                        <option value="staff">Staff Members / Providers</option>
                        <option value="client">Registered Clients</option>
                    </select>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card glass-card border-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 80px;">ID</th>
                                <th scope="col">User Identity</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Access Role</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="table-row-hover">
                                <td>{{ user.id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials mr-3" :class="getAvatarColorClass(user.id)">
                                            {{ getInitials(user.name) }}
                                        </div>
                                        <div>
                                            <span class="font-weight-bold d-block mb-0 text-sm" style="color: #0f172a;">{{ user.name }}</span>
                                            <span class="text-muted d-block small">{{ user.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ user.phone || '-' }}</td>
                                <td>
                                    <span class="status-badge" :class="'status-' + (user.role === 'admin' ? 'confirmed' : user.role === 'staff' ? 'scheduled' : 'completed')">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button @click="openEditDrawer(user)" class="btn btn-sm btn-outline-primary mr-2" style="border-radius: 8px; font-weight: 600; font-size: 13px;">
                                        <i class="far fa-edit"></i> Edit
                                    </button>
                                    <button @click="deleteUser(user)" class="btn btn-sm btn-outline-danger" style="border-radius: 8px; font-weight: 600; font-size: 13px;">
                                        <i class="far fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-2x mb-2 d-block text-primary-light"></i>
                                    No accounts match your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="pagination.last_page > 1" class="card-footer bg-white border-0 d-flex justify-content-between align-items-center" style="border-radius: 0 0 16px 16px; padding: 18px 20px;">
                    <div class="text-muted small">
                        Showing page {{ pagination.current_page }} of {{ pagination.last_page }}
                    </div>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': pagination.current_page === 1 }">
                            <a class="page-link" href="#" @click.prevent="fetchUsers(pagination.current_page - 1)">&laquo;</a>
                        </li>
                        <li v-for="page in pagination.last_page" :key="page" class="page-item" :class="{ 'active': pagination.current_page === page }">
                            <a class="page-link" href="#" @click.prevent="fetchUsers(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ 'disabled': pagination.current_page === pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="fetchUsers(pagination.current_page + 1)">&raquo;</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Slide-over Drawer -->
            <div v-if="isDrawerOpen" class="drawer-backdrop" @click="closeDrawer"></div>
            <div class="drawer-right" :class="{ 'open': isDrawerOpen }">
                <div class="drawer-header">
                    <h5 class="font-weight-bold mb-0" style="color: #0f172a; font-size: 18px;">
                        {{ isEditing ? 'Edit User details' : 'Create User profile' }}
                    </h5>
                    <button type="button" @click="closeDrawer" class="close border-0 bg-transparent" style="font-size: 24px;">&times;</button>
                </div>
                
                <form @submit.prevent="handleSubmit" class="d-flex flex-column h-100">
                    <div class="drawer-body">
                        <div class="form-group mb-3">
                            <label class="form-label">Full Name</label>
                            <input v-model="form.name" type="text" class="form-control-modern w-100" placeholder="Jane Smith" required>
                            <div v-if="errors.name" class="error-feedback">{{ errors.name[0] }}</div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Email Address</label>
                            <input v-model="form.email" type="email" class="form-control-modern w-100" placeholder="jane@example.com" required>
                            <div v-if="errors.email" class="error-feedback">{{ errors.email[0] }}</div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Phone Number</label>
                            <input v-model="form.phone" type="text" class="form-control-modern w-100" placeholder="+1 (555) 000-0000">
                            <div v-if="errors.phone" class="error-feedback">{{ errors.phone[0] }}</div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">System Role</label>
                            <select v-model="form.role" class="form-control-modern w-100" required>
                                <option value="client">Client</option>
                                <option value="staff">Staff / Provider</option>
                                <option value="admin">Administrator</option>
                            </select>
                            <div v-if="errors.role" class="error-feedback">{{ errors.role[0] }}</div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label">Password</label>
                            <input v-model="form.password" type="password" class="form-control-modern w-100" placeholder="••••••••" :required="!isEditing">
                            <span class="input-label-subtitle" v-if="isEditing">Leave password empty to retain the current credential.</span>
                            <div v-if="errors.password" class="error-feedback">{{ errors.password[0] }}</div>
                        </div>
                    </div>

                    <div class="drawer-footer">
                        <button type="button" @click="closeDrawer" class="btn btn-light px-4" style="height: 42px; border-radius: 8px;">Cancel</button>
                        <button type="submit" class="btn btn-gradient px-4" style="height: 42px; border-radius: 8px;" :disabled="isSubmitting">
                            <span v-if="isSubmitting"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                            <span v-else>Save details</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-indigo { background-color: #6366f1; }
.bg-purple { background-color: #a855f7; }
.bg-teal { background-color: #14b8a6; }
.bg-pink { background-color: #ec4899; }
.bg-orange { background-color: #f97316; }
.bg-blue { background-color: #3b82f6; }
</style>