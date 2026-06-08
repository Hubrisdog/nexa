<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Models\User;
use App\Models\Tenant;
use App\Helpers\Demo;
use App\Services\DemoService;
use App\Services\AiService;
use App\Services\SequenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class DemoController extends Controller
{
    protected $demoService;

    public function __construct(DemoService $demoService)
    {
        $this->demoService = $demoService;
    }
    /**
     * Simulates a full end-to-end client booking, CRM deal score, and calendar sync workflow.
     */
    public function runSimulation(Request $request, AiService $aiService, SequenceService $sequenceService)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        // 1. Resolve client and staff provider
        $client = User::where('role', 'client')->inRandomOrder()->first();
        $staff = User::where('role', 'staff')->inRandomOrder()->first();

        if (!$client || !$staff) {
            return response()->json(['message' => 'No seeded clients or staff found to simulate booking.'], 422);
        }

        // 2. Generate B2B Lead Company & Contact
        $industries = ['FinTech', 'SaaS', 'HealthTech', 'Aerospace', 'AI & Robotics'];
        $industry = $industries[array_rand($industries)];
        
        $companyNames = ['Stark Industries', 'Wayne Enterprises', 'Umbrella Corp', 'Cyberdyne Systems', 'Tyrell Corp', 'Oscorp Tech', 'Soylent Corp', 'Hanso Foundation'];
        $companyName = $companyNames[array_rand($companyNames)] . ' ' . rand(10, 99);

        $company = Company::create([
            'name' => $companyName,
            'industry' => $industry,
            'website' => 'https://' . strtolower(str_replace(' ', '', $companyName)) . '.com',
            'revenue' => '$' . rand(5, 80) . 'M',
            'employee_count' => rand(50, 1000),
            'tenant_id' => $tenantId,
        ]);

        $contactNames = ['Pepper Potts', 'Lucius Fox', 'Albert Wesker', 'Miles Dyson', 'Rachael Deckard', 'Peter Parker', 'Kate Austen', 'Jack Shephard'];
        $contactName = $contactNames[array_rand($contactNames)];

        $contact = Contact::create([
            'company_id' => $company->id,
            'name' => $contactName,
            'position' => 'VP of Business Relations',
            'email' => strtolower(str_replace(' ', '.', $contactName)) . '@' . strtolower(str_replace(' ', '', $company->name)) . '.com',
            'phone' => '+1 (555) 019-' . rand(1000, 9999),
            'linkedin_url' => 'https://linkedin.com/in/' . strtolower(str_replace(' ', '', $contactName)),
            'tenant_id' => $tenantId,
        ]);

        // 3. Create Opportunity (Deal) and Auto-Score using AiService
        $dealValue = rand(15, 120) * 1000;
        $stages = ['cold', 'contacted', 'interested'];
        $stage = $stages[array_rand($stages)];

        $deal = Deal::create([
            'title' => "{$company->name} Enterprise License Sync",
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'value' => $dealValue,
            'stage' => $stage,
            'tenant_id' => $tenantId,
        ]);

        // AI Score lead evaluation
        $score = $aiService->scoreLead($deal);
        $deal->update(['score' => $score]);

        // 4. Log Discovery Call Activity
        Activity::create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'user_id' => auth()->id(),
            'type' => 'call',
            'description' => "Initial discovery connection with {$contact->name}. Outlined core integration scaling scopes.",
            'tenant_id' => $tenantId,
        ]);

        // 5. Book Meeting (Appointment)
        $startHour = rand(9, 16);
        $startTime = now()->addDays(rand(2, 8))->setHour($startHour)->setMinute(0)->setSecond(0);
        $endTime = (clone $startTime)->addMinutes(30);

        $providerType = rand(0, 1) ? 'google' : 'outlook';

        $appointment = Appointment::create([
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'title' => "Enterprise Scope Sync - {$company->name}",
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'scheduled',
            'calendar_provider' => $providerType,
            'note' => "System generated simulation sync slot for B2B deal: {$deal->title}.",
            'tenant_id' => $tenantId,
        ]);

        // 6. Run Calendar sync
        $googleService = resolve(\App\Services\GoogleCalendarService::class);
        $outlookService = resolve(\App\Services\OutlookCalendarService::class);

        if ($appointment->calendar_provider === 'google') {
            $syncRes = $googleService->syncAppointment($appointment, 'create');
            $appointment->update([
                'google_event_id' => $syncRes['event_id'],
                'meeting_link' => $syncRes['meeting_link'],
            ]);
        } else {
            $syncRes = $outlookService->syncAppointment($appointment, 'create');
            $appointment->update([
                'outlook_event_id' => $syncRes['event_id'],
                'meeting_link' => $syncRes['meeting_link'],
            ]);
        }

        // 7. Trigger notification sequences
        $sequenceService->triggerAppointmentSequences($appointment);

        // 8. Log the meeting activity link
        Activity::create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'user_id' => auth()->id(),
            'type' => 'meeting',
            'description' => "Appointment scheduled: '{$appointment->title}'. Meeting Join Link: {$appointment->meeting_link}",
            'tenant_id' => $tenantId,
        ]);

        // Move Deal Stage to Booked
        $deal->update(['stage' => 'booked']);

        return response()->json([
            'success' => true,
            'company' => $company,
            'contact' => $contact,
            'deal' => $deal,
            'appointment' => $appointment,
        ]);
    }

    /**
     * Automatically logs the user into the demo tenant and redirects to the dashboard.
     */
    public function autoLoginDemo(Request $request)
    {
        // Disable tenant scope during demo login to resolve any conflicting session state
        config(['app.ignore_tenant_scope' => true]);

        // 1. Create or Find Demo Tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Demo Workspace',
                'plan' => 'enterprise',
                'is_demo' => true,
            ]
        );

        if (!$tenant->is_demo) {
            $tenant->update(['is_demo' => true]);
        }

        // 2. Create Demo Admin User
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tenant_id' => $tenant->id,
            ]
        );

        // Ensure user is associated with demo tenant
        if ($user->tenant_id !== $tenant->id) {
            $user->update(['tenant_id' => $tenant->id]);
        }

        // 3. Seed demo data if the workspace is empty
        if (Company::where('tenant_id', $tenant->id)->count() === 0) {
            $this->demoService->resetWorkspace();
        }

        // 4. Log the user in using session auth
        Auth::login($user);

        // 5. Redirect straight to dashboard
        return redirect('/admin/dashboard');
    }

    /**
     * Renders the simulated local Google OAuth consent screen for Demo Mode.
     */
    public function googleConsentScreen()
    {
        return view('admin.demo-oauth-consent');
    }

    /**
     * Triggers a safe database wipe and rebuild for the demo workspace.
     */
    public function resetDemoData(Request $request)
    {
        if (!Demo::active()) {
            return response()->json(['message' => 'Demo reset is only available in demo mode.'], 403);
        }

        try {
            $this->demoService->resetWorkspace();
            return response()->json(['success' => true, 'message' => 'Demo workspace successfully reset.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to reset demo data: " . $e->getMessage());
            return response()->json(['message' => 'Failed to reset demo workspace: ' . $e->getMessage()], 500);
        }
    }
}
