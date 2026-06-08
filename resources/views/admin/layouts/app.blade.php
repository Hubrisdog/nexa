<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nexa | Dashboard</title>

    <script>
        window.NexaUser = @json(auth()->user() ? auth()->user()->load('tenant') : null);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper" id="app">

        <!-- Logout Overlay -->
        <div v-if="isLoggingOut" class="logout-overlay">
            <div class="spinner-border text-indigo mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Securing session...</span>
            </div>
            <h4 class="font-weight-bold text-white mb-1" style="font-size: 22px; letter-spacing: -0.5px;">Signing out securely</h4>
            <p class="text-muted text-sm">Clearing credentials and redirecting to login portal...</p>
        </div>

        <!-- Top Header Navbar -->
        <nav v-if="$route.name !== 'login' && $route.name !== 'register'" class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <!-- Top-Center Universal Search Trigger (Cmd/Ctrl + K) -->
            <div class="d-none d-md-flex align-items-center justify-content-center mx-auto" style="flex-grow: 1; max-width: 400px; position: relative;">
                <button @click.prevent="$refs.commandSearch.openModal()" class="w-100 text-left d-flex align-items-center justify-content-between px-3" style="background-color: var(--bg-dark-hover); color: var(--text-secondary); height: 40px; cursor: pointer; border: 1px solid var(--border-dark); font-size: 13.5px; border-radius: 8px;">
                    <span><i class="fas fa-search mr-2"></i> Search anything...</span>
                    <kbd class="bg-dark text-muted font-weight-bold" style="font-size: 10px; border: 1px solid var(--border-dark); padding: 2px 6px; border-radius: 4px;">Ctrl K</kbd>
                </button>
            </div>

            <ul class="navbar-nav ml-auto align-items-center">
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#" style="gap: 8px; padding-right: 1rem;">
                        <div class="avatar-initials-xs bg-indigo">
                            @{{ getInitials(currentUser?.name) }}
                        </div>
                        <span class="d-none d-sm-inline font-weight-bold text-sm" style="color: #cbd5e1; margin-left: 6px;">@{{ currentUser?.name || 'Admin User' }}</span>
                        <i class="fas fa-chevron-down text-muted ml-1" style="font-size: 10px;"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow-lg border-0 mt-2" style="border-radius: 12px; min-width: 200px; padding: 8px 0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                        <div class="px-3 py-2 border-bottom">
                            <span class="d-block font-weight-bold text-sm" style="color: #cbd5e1;">@{{ currentUser?.name }}</span>
                            <span class="d-block text-xs text-muted">@{{ currentUser?.email }}</span>
                        </div>
                        <router-link to="/admin/profile" class="dropdown-item py-2 px-3 d-flex align-items-center" style="gap: 10px; color: #cbd5e1; font-size: 14px;">
                            <i class="far fa-user text-muted mr-1"></i> Account Profile
                        </router-link>
                        <router-link to="/admin/settings" class="dropdown-item py-2 px-3 d-flex align-items-center" style="gap: 10px; color: #cbd5e1; font-size: 14px;">
                            <i class="fas fa-cog text-muted mr-1"></i> System Settings
                        </router-link>
                        <div class="dropdown-divider"></div>
                        <a href="#" @click.prevent="logout" class="dropdown-item py-2 px-3 d-flex align-items-center text-danger" style="gap: 10px; font-size: 14px;">
                            <i class="fas fa-sign-out-alt mr-1"></i> Sign Out
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Sidebar Navigation Menu -->
        <aside v-if="$route.name !== 'login' && $route.name !== 'register'" class="main-sidebar sidebar-dark-primary elevation-4">

            <router-link to="/admin/dashboard" class="brand-link d-flex align-items-center" style="gap: 10px;">
                <div class="brand-logo-icon">
                    <span>N</span>
                </div>
                <span class="brand-text">Nexa</span>
            </router-link>

            <div class="sidebar">

                <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
                    <div class="image">
                        <div class="avatar-initials-sm bg-indigo">
                            @{{ getInitials(currentUser?.name) }}
                        </div>
                    </div>
                    <div class="info ml-2">
                        <router-link to="/admin/profile" class="d-block font-weight-bold" style="color: #cbd5e1; font-size: 14px;">@{{ currentUser?.name || 'Admin User' }}</router-link>
                    </div>
                </div>

                <!-- Demo Mode Warning Banner -->
                <div v-if="currentUser?.tenant?.is_demo || currentUser?.tenant_id === 999" class="px-3 mb-3">
                    <div class="p-3 text-center" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: var(--border-radius-md); backdrop-filter: blur(4px);">
                        <div class="text-warning font-weight-bold text-sm mb-1">
                            <i class="fas fa-flask mr-1"></i> Demo Mode
                        </div>
                        <div class="text-muted text-xs mb-2">
                            Changes are not saved permanently.
                        </div>
                        <button @click.prevent="resetDemoData" :disabled="isResettingDemo" class="btn btn-xs btn-outline-warning w-100 py-1" style="font-size: 11px; font-weight: 600; border-radius: 6px; cursor: pointer;">
                            <span v-if="isResettingDemo"><i class="fas fa-spinner fa-spin mr-1"></i> Resetting...</span>
                            <span v-else><i class="fas fa-undo mr-1"></i> Reset Demo Data</span>
                        </button>
                    </div>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        <li class="nav-item">
                            <router-link to="/admin/dashboard" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>
                                    Dashboard
                                </p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/appointment" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>
                                    Appointments
                                </p>
                            </router-link>
                        </li>
                        <li v-if="currentUser?.role === 'admin' || currentUser?.role === 'staff'" class="nav-item">
                            <router-link to="/admin/crm" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-briefcase"></i>
                                <p>
                                    B2B CRM Pipeline
                                </p>
                            </router-link>
                        </li>
                        <li v-if="currentUser?.role === 'admin' || currentUser?.role === 'staff'" class="nav-item">
                            <router-link to="/admin/analytics" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-chart-bar"></i>
                                <p>
                                    Analytics Funnel
                                </p>
                            </router-link>
                        </li>
                        <li v-if="currentUser?.role === 'admin'" class="nav-item">
                            <router-link to="/admin/users" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    Users
                                </p>
                            </router-link>
                        </li>
                        <li v-if="currentUser?.role === 'admin'" class="nav-item">
                            <router-link to="/admin/settings" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-cog"></i>
                                <p>
                                    Settings
                                </p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/profile" active-class="active" class="nav-link">
                                <i class="nav-icon fas fa-user-circle"></i>
                                <p>
                                    Profile
                                </p>
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <a href="#" @click.prevent="logout" class="nav-link text-danger">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>
                                    Logout
                                </p>
                            </a>
                        </li>
                    </ul>
                </nav>

            </div>

        </aside>

        <!-- Main Content Area -->
        <div :class="{ 'content-wrapper': $route.name !== 'login' && $route.name !== 'register' }">
            <router-view></router-view>
        </div>

        <!-- Mobile Bottom Navigation (Visible on mobile viewports) -->
        <div v-if="$route.name !== 'login' && $route.name !== 'register'" class="mobile-bottom-nav">
            <router-link to="/admin/dashboard" active-class="active" class="mobile-nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </router-link>
            <router-link to="/admin/appointment" active-class="active" class="mobile-nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </router-link>
            <router-link to="/admin/crm" active-class="active" class="mobile-nav-item">
                <i class="fas fa-briefcase"></i>
                <span>CRM</span>
            </router-link>
            <router-link to="/admin/analytics" active-class="active" class="mobile-nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </router-link>
            <router-link to="/admin/profile" active-class="active" class="mobile-nav-item">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </router-link>
        </div>

        <aside class="control-sidebar control-sidebar-dark">
            <div class="p-3">
                <h5>Title</h5>
                <p>Sidebar content</p>
            </div>
        </aside>

        <!-- Command Search overlay component mounted globally -->
        <command-search ref="commandSearch"></command-search>

        <footer v-if="$route.name !== 'login' && $route.name !== 'register'" class="main-footer text-center py-3">
            <div class="float-right d-none d-sm-inline">
                Version 1.0.0
            </div>
            <span class="text-muted">Copyright &copy; 2026 <strong>Nexa Scheduler</strong>. All rights reserved.</span>
        </footer>
    </div>
</body>

</html>