<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use App\Services\AvailabilityService;
use App\Services\AiService;
use App\Services\SequenceService;
use App\Jobs\SyncCalendarJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicBookingController extends Controller
{
    /**
     * Resolves provider details using a slug or username.
     */
    public function getProvider($username)
    {
        $slug = str_replace('-', ' ', $username);
        
        $provider = User::where(function($q) use ($slug, $username) {
            $q->where('name', 'like', "%{$slug}%")
              ->orWhere('email', 'like', "%{$username}%");
        })->whereIn('role', ['staff', 'admin'])->first();

        if (!$provider) {
            return response()->json(['message' => 'Provider not found.'], 404);
        }

        // Fetch or create default availability
        $availability = $provider->availability;
        if (!$availability) {
            $availability = $provider->availability()->create([
                'working_hours' => [
                    'monday' => ['start' => '09:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                    'thursday' => ['start' => '09:00', 'end' => '17:00'],
                    'friday' => ['start' => '09:00', 'end' => '17:00'],
                ],
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'holidays' => [],
                'buffer_time' => 15,
                'timezone' => 'UTC',
                'tenant_id' => $provider->tenant_id,
            ]);
        }

        return response()->json([
            'id' => $provider->id,
            'name' => $provider->name,
            'email' => $provider->email,
            'avatar' => $provider->avatar,
            'timezone' => $availability->timezone ?? 'UTC',
            'working_hours' => $availability->working_hours,
            'buffer_time' => $availability->buffer_time,
            'tenant_id' => $provider->tenant_id,
        ]);
    }

    /**
     * Calculates available 30-minute booking slots for a provider on a specific date.
     */
    public function getAvailableSlots($username, Request $request, AvailabilityService $availabilityService)
    {
        $slug = str_replace('-', ' ', $username);
        $provider = User::where(function($q) use ($slug, $username) {
            $q->where('name', 'like', "%{$slug}%")
              ->orWhere('email', 'like', "%{$username}%");
        })->whereIn('role', ['staff', 'admin'])->first();

        if (!$provider) {
            return response()->json(['message' => 'Provider not found.'], 404);
        }

        $dateStr = $request->query('date');
        if (!$dateStr) {
            return response()->json(['message' => 'Date parameter is required (Y-m-d).'], 400);
        }

        $availability = $provider->availability;
        $timezone = $availability->timezone ?? 'UTC';
        $clientTimezone = $request->query('timezone', $timezone);

        $carbonDate = Carbon::parse($dateStr);
        $dayOfWeek = strtolower($carbonDate->englishDayOfWeek);
        
        $workingHours = $availability->working_hours ?? [
            'monday' => ['start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['start' => '09:00', 'end' => '17:00'],
            'wednesday' => ['start' => '09:00', 'end' => '17:00'],
            'thursday' => ['start' => '09:00', 'end' => '17:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
        ];

        if (!isset($workingHours[$dayOfWeek])) {
            return response()->json([]); // Provider doesn't work this day
        }

        $hours = $workingHours[$dayOfWeek];
        $start = Carbon::parse($dateStr . ' ' . $hours['start'], $timezone);
        $end = Carbon::parse($dateStr . ' ' . $hours['end'], $timezone);

        $slots = [];
        $current = $start->copy();

        while ($current->copy()->addMinutes(30)->lte($end)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes(30);

            // Run core conflict and buffer check
            $check = $availabilityService->checkAvailability($provider, $slotStart, $slotEnd);

            $slots[] = [
                'start' => $slotStart->toIso8601String(),
                'end' => $slotEnd->toIso8601String(),
                'time_label' => $slotStart->copy()->setTimezone($clientTimezone)->format('g:i A'),
                'available' => $check['available'],
                'reason' => $check['reason']
            ];

            $current->addMinutes(30);
        }

        return response()->json($slots);
    }

    /**
     * Creates the booking, client profile, B2B CRM company/contact/deal, and runs triggers.
     */
    public function bookSlot(Request $request, AvailabilityService $availabilityService, AiService $aiService, SequenceService $sequenceService)
    {
        $fields = $request->validate([
            'provider_id' => ['required', 'exists:users,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $provider = User::find($fields['provider_id']);
        $tenantId = $provider->tenant_id ?? 1;

        // Check availability once more before creating booking to prevent double-booking race condition
        $check = $availabilityService->checkAvailability($provider, $fields['start_time'], $fields['end_time']);
        if (!$check['available']) {
            return response()->json(['message' => 'The selected slot is no longer available: ' . $check['reason']], 422);
        }

        // 1. Resolve/Create Client profile scoped to the tenant
        $client = User::where('email', $fields['client_email'])->first();
        if (!$client) {
            $client = new User([
                'name' => $fields['client_name'],
                'email' => $fields['client_email'],
                'role' => 'client',
                'password' => bcrypt(Str::random(16)),
                'tenant_id' => $tenantId
            ]);
            $client->save();
        }

        // 2. SaaS B2B CRM Automatic Sync: Extract domain details to create Company
        $domain = substr(strrchr($fields['client_email'], "@"), 1);
        $genericDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com', 'aol.com', 'icloud.com', 'mail.com'];
        if (in_array(strtolower($domain), $genericDomains)) {
            $companyName = explode(' ', $fields['client_name'])[0] . ' Co';
        } else {
            $companyName = ucwords(explode('.', $domain)[0]);
        }

        // Find/Create CRM Company
        $company = Company::where('name', $companyName)->where('tenant_id', $tenantId)->first();
        if (!$company) {
            $company = Company::create([
                'name' => $companyName,
                'industry' => 'SaaS / Web',
                'website' => 'https://' . strtolower(str_replace(' ', '', $companyName)) . '.com',
                'revenue' => '$' . rand(1, 10) . 'M',
                'employee_count' => rand(10, 200),
                'tenant_id' => $tenantId
            ]);
        }

        // Find/Create CRM Contact
        $contact = Contact::where('email', $fields['client_email'])->where('tenant_id', $tenantId)->first();
        if (!$contact) {
            $contact = Contact::create([
                'company_id' => $company->id,
                'name' => $fields['client_name'],
                'position' => 'Strategic Partner',
                'email' => $fields['client_email'],
                'phone' => '+1 (555) ' . rand(100, 999) . '-' . rand(1000, 9999),
                'tenant_id' => $tenantId
            ]);
        }

        // Create qualified CRM Deal
        $deal = Deal::create([
            'title' => "{$company->name} Scope Sync (Auto-Booked)",
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'value' => rand(10, 50) * 1000,
            'stage' => 'booked',
            'tenant_id' => $tenantId
        ]);

        // Auto-score B2B lead
        $score = $aiService->scoreLead($deal);
        $deal->update(['score' => $score]);

        // Log Discovery Activity
        Activity::create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'user_id' => $provider->id,
            'type' => 'meeting',
            'description' => "Appointment automatically booked via shareable link: " . ($fields['notes'] ?? 'No comments provided.'),
            'tenant_id' => $tenantId
        ]);

        // 3. Create the Appointment
        $appointment = new Appointment([
            'client_id' => $client->id,
            'staff_id' => $provider->id,
            'title' => "Discovery Sync - {$company->name}",
            'start_time' => Carbon::parse($fields['start_time']),
            'end_time' => Carbon::parse($fields['end_time']),
            'status' => 'scheduled',
            'note' => $fields['notes'],
            'calendar_provider' => 'google', // Defaults to Google Calendar
            'tenant_id' => $tenantId
        ]);
        $appointment->save();

        // 4. Run Calendar sync & sequences
        try {
            $googleService = resolve(\App\Services\GoogleCalendarService::class);
            $syncRes = $googleService->syncAppointment($appointment, 'create');
            $appointment->update([
                'google_event_id' => $syncRes['event_id'],
                'meeting_link' => $syncRes['meeting_link'],
            ]);
        } catch (\Exception $e) {
            \Log::warning("Public booking Google Calendar sync failed: " . $e->getMessage());
        }

        // Trigger email/SMS notification sequence
        try {
            $sequenceService->triggerAppointmentSequences($appointment);
        } catch (\Exception $e) {
            \Log::warning("Public booking notifications failed: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'appointment' => $appointment->load(['client', 'staff']),
            'deal' => $deal,
            'company' => $company
        ], 201);
    }
}
