<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Seed Default Tenant
        $tenant = \App\Models\Tenant::create([
            'name' => 'Acme Organization',
            'slug' => 'acme-org',
            'plan' => 'free',
        ]);

        // 1. Seed Admin User
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1 (555) 019-2834',
            'tenant_id' => $tenant->id,
        ]);

        // 2. Seed Staff Users
        $staff1 = User::factory()->create([
            'name' => 'Dr. Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'phone' => '+1 (555) 014-9988',
            'tenant_id' => $tenant->id,
        ]);

        $staff2 = User::factory()->create([
            'name' => 'Dr. John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'phone' => '+1 (555) 012-7766',
            'tenant_id' => $tenant->id,
        ]);

        $staff3 = User::factory()->create([
            'name' => 'Sarah Johnson',
            'email' => 'sarah.j@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'phone' => '+1 (555) 017-3344',
            'tenant_id' => $tenant->id,
        ]);

        // 3. Seed Client Users
        $clients = User::factory(15)->create([
            'role' => 'client',
            'tenant_id' => $tenant->id,
        ]);

        // 4. Seed Settings
        Setting::create(['key' => 'app_name', 'value' => 'Nexa Console', 'tenant_id' => $tenant->id]);
        Setting::create(['key' => 'business_email', 'value' => 'info@aurabooking.com', 'tenant_id' => $tenant->id]);
        Setting::create(['key' => 'business_hours', 'value' => '09:00 - 18:00', 'tenant_id' => $tenant->id]);
        Setting::create(['key' => 'booking_interval', 'value' => '30 mins', 'tenant_id' => $tenant->id]);
        Setting::create(['key' => 'enable_notifications', 'value' => 'true', 'tenant_id' => $tenant->id]);

        // 5. Seed Appointments
        $services = ['General Consultation', 'Teeth Cleaning', 'Follow-up Checkup', 'Emergency Visit', 'Therapy Session'];
        $statuses = ['scheduled', 'confirmed', 'completed', 'cancelled'];
        
        $staffMembers = [$staff1, $staff2, $staff3];

        for ($i = 0; $i < 15; $i++) {
            $client = $clients->random();
            $staff = $staffMembers[array_rand($staffMembers)];
            $status = $statuses[array_rand($statuses)];
            $title = $services[array_rand($services)];
            
            // Random date in current month
            $daysOffset = rand(-15, 15);
            $startHour = rand(9, 17);
            $startTime = now()->addDays($daysOffset)->setHour($startHour)->setMinute(0)->setSecond(0);
            $endTime = (clone $startTime)->addMinutes(30);

            Appointment::create([
                'client_id' => $client->id,
                'staff_id' => $staff->id,
                'title' => $title,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status,
                'note' => fake()->sentence(),
                'calendar_provider' => 'google',
                'google_event_id' => 'mock-google-event-' . $i,
                'meeting_link' => 'https://meet.google.com/nexa-mock-' . $i,
                'tenant_id' => $tenant->id,
            ]);
        }

        // 6. Seed Availabilities for Staff
        foreach ($staffMembers as $index => $staff) {
            $timezones = ['America/New_York', 'Europe/London', 'Asia/Singapore'];
            \App\Models\Availability::create([
                'user_id' => $staff->id,
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
                'holidays' => ['2026-12-25', '2026-07-04'],
                'buffer_time' => 15,
                'timezone' => $timezones[$index % 3],
                'tenant_id' => $tenant->id,
            ]);
        }

        // 7. Seed B2B CRM Data
        $companiesData = [
            ['name' => 'Acme Corporation', 'industry' => 'Technology', 'website' => 'https://acme.corp', 'revenue' => '$10M', 'employee_count' => 120, 'tenant_id' => $tenant->id],
            ['name' => 'Globex Corporation', 'industry' => 'Manufacturing', 'website' => 'https://globex.co', 'revenue' => '$45M', 'employee_count' => 500, 'tenant_id' => $tenant->id],
            ['name' => 'Initech LLC', 'industry' => 'Finance / Fintech', 'website' => 'https://initech.io', 'revenue' => '$2.4M', 'employee_count' => 35, 'tenant_id' => $tenant->id],
        ];

        $seededCompanies = [];
        foreach ($companiesData as $companyData) {
            $seededCompanies[] = \App\Models\Company::create($companyData);
        }

        $contactsData = [
            ['company_id' => $seededCompanies[0]->id, 'name' => 'John Smith', 'position' => 'Head of Procurement', 'email' => 'john.smith@acme.corp', 'phone' => '+1 (555) 019-2834', 'linkedin_url' => 'https://linkedin.com/in/johnsmith-acme', 'tenant_id' => $tenant->id],
            ['company_id' => $seededCompanies[1]->id, 'name' => 'Alice Vance', 'position' => 'HR Director', 'email' => 'alice.vance@globex.co', 'phone' => '+1 (555) 014-9988', 'linkedin_url' => 'https://linkedin.com/in/alicevance-globex', 'tenant_id' => $tenant->id],
            ['company_id' => $seededCompanies[2]->id, 'name' => 'Peter Gibbons', 'position' => 'Dev Lead', 'email' => 'peter@initech.io', 'phone' => '+1 (555) 012-7766', 'linkedin_url' => 'https://linkedin.com/in/petergibbons-initech', 'tenant_id' => $tenant->id],
        ];

        $seededContacts = [];
        foreach ($contactsData as $contactData) {
            $seededContacts[] = \App\Models\Contact::create($contactData);
        }

        $dealsData = [
            ['title' => 'Acme Enterprise Integration Plan', 'company_id' => $seededCompanies[0]->id, 'contact_id' => $seededContacts[0]->id, 'value' => 25000.00, 'stage' => 'interested', 'score' => 85, 'tenant_id' => $tenant->id],
            ['title' => 'Globex Multi-Tenant Cloud Migration', 'company_id' => $seededCompanies[1]->id, 'contact_id' => $seededContacts[1]->id, 'value' => 85000.00, 'stage' => 'contacted', 'score' => 60, 'tenant_id' => $tenant->id],
            ['title' => 'Initech Consultancy Renewal', 'company_id' => $seededCompanies[2]->id, 'contact_id' => $seededContacts[2]->id, 'value' => 9500.00, 'stage' => 'booked', 'score' => 95, 'tenant_id' => $tenant->id],
            ['title' => 'Stark Industries Automation Project', 'company_id' => null, 'contact_id' => null, 'value' => 150000.00, 'stage' => 'cold', 'score' => 40, 'tenant_id' => $tenant->id],
            ['title' => 'Wayne Enterprises Security Audit', 'company_id' => null, 'contact_id' => null, 'value' => 60000.00, 'stage' => 'closed_won', 'score' => 100, 'tenant_id' => $tenant->id],
            ['title' => 'Cyberdyne Systems Hardware Deal', 'company_id' => null, 'contact_id' => null, 'value' => 40000.00, 'stage' => 'closed_lost', 'score' => 20, 'tenant_id' => $tenant->id],
        ];

        foreach ($dealsData as $deal) {
            \App\Models\Deal::create($deal);
        }

        // Log some initial activities
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            \App\Models\Activity::create([
                'company_id' => $seededCompanies[0]->id,
                'contact_id' => $seededContacts[0]->id,
                'user_id' => $admin->id,
                'type' => 'call',
                'description' => 'Discovery call completed. John Smith is interested in upgrading the integration scale.',
                'tenant_id' => $tenant->id,
            ]);
            \App\Models\Activity::create([
                'company_id' => $seededCompanies[1]->id,
                'contact_id' => $seededContacts[1]->id,
                'user_id' => $admin->id,
                'type' => 'email',
                'description' => 'Sent introductory deck and API documents to Alice Vance.',
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
