<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CrmController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\AiController;
use App\Http\Controllers\Admin\DemoController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\OAuthController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web & API Routes
|--------------------------------------------------------------------------
*/

// Auth API Routes (Public)
Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/register', [AuthController::class, 'register']);

// Public Booking API Routes
Route::get('/api/public/booking/{username}', [PublicBookingController::class, 'getProvider']);
Route::get('/api/public/booking/{username}/slots', [PublicBookingController::class, 'getAvailableSlots']);
Route::post('/api/public/book', [PublicBookingController::class, 'bookSlot'])->middleware('tenant.limit');
Route::get('/api/public/appointments/{appointment}/ics', [PublicBookingController::class, 'downloadIcs']);
Route::get('/api/public/workspace', [PublicBookingController::class, 'getWorkspace']);

// OAuth API Callback Routes
Route::get('/api/oauth/google/callback', [OAuthController::class, 'googleCallback'])->name('oauth.google.callback');

// Demo Mode Routes (Public)
Route::get('/demo', [DemoController::class, 'autoLoginDemo']);
Route::get('/demo/oauth/google', [DemoController::class, 'googleConsentScreen']);

// Protected API Routes (Requires web session authentication)
Route::middleware('auth')->group(function () {
    Route::post('/api/logout', [AuthController::class, 'logout']);
    Route::get('/api/user', [AuthController::class, 'me']);
    
    // Global Search
    Route::get('/api/search', [GlobalSearchController::class, 'search']);
    
    // Dashboard Stats
    Route::get('/api/dashboard-stats', [DashboardController::class, 'stats']);

    // User Management CRUD
    Route::apiResource('/api/users', UserController::class)->middleware('tenant.limit');

    // Appointment Scheduling CRUD
    Route::apiResource('/api/appointments', AppointmentController::class)->middleware('tenant.limit');

    // Settings
    Route::get('/api/settings', [SettingController::class, 'index']);
    Route::post('/api/settings', [SettingController::class, 'update']);
    Route::post('/api/availability', [SettingController::class, 'updateAvailability']);
    Route::post('/api/settings/branding', [SettingController::class, 'updateBranding']);

    // Calendar Integrations (OAuth)
    Route::get('/api/oauth/google/redirect', [OAuthController::class, 'googleRedirect'])->name('oauth.google.redirect');
    Route::get('/api/oauth/connections', [OAuthController::class, 'getConnections']);
    Route::delete('/api/oauth/connections/{provider}', [OAuthController::class, 'disconnect']);

    // Profile
    Route::get('/api/profile', [ProfileController::class, 'show']);
    Route::put('/api/profile', [ProfileController::class, 'update']);

    // B2B CRM & Kanban Board Routes
    Route::get('/api/crm/pipeline', [CrmController::class, 'getPipeline']);
    Route::put('/api/crm/deals/{deal}/stage', [CrmController::class, 'updateDealStage']);
    Route::post('/api/crm/deals', [CrmController::class, 'storeDeal']);
    Route::delete('/api/crm/deals/{deal}', [CrmController::class, 'destroyDeal']);
    
    Route::get('/api/crm/companies', [CrmController::class, 'getCompanies']);
    Route::post('/api/crm/companies', [CrmController::class, 'storeCompany']);
    
    Route::get('/api/crm/contacts', [CrmController::class, 'getContacts']);
    Route::post('/api/crm/contacts', [CrmController::class, 'storeContact']);
    
    Route::get('/api/crm/activities', [CrmController::class, 'getActivities']);
    Route::post('/api/crm/activities', [CrmController::class, 'storeActivity']);

    // Analytics Dashboard Route
    Route::get('/api/analytics', [AnalyticsController::class, 'getAnalyticsData']);

    // SaaS Billing Routes
    Route::get('/api/billing/details', [BillingController::class, 'getBillingDetails']);
    Route::post('/api/billing/subscribe', [BillingController::class, 'updateSubscription']);

    // AI Summarization Route
    Route::post('/api/appointments/{appointment}/summarize', [AiController::class, 'summarize'])->middleware('tenant.limit');

    // Demo Simulation Route
    Route::post('/api/demo/simulate', [DemoController::class, 'runSimulation']);
    Route::post('/api/demo/reset', [DemoController::class, 'resetDemoData']);
});

// Public Stripe Webhook Route
Route::post('/api/billing/webhook', [BillingController::class, 'stripeWebhook']);

// Single Page Application (SPA) - Catch-All View Route
Route::get('/login', ApplicationController::class)->name('login');
Route::get('/', ApplicationController::class);
Route::get('{view}', ApplicationController::class)->where('view', '(.*)');