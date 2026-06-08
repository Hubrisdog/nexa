<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Appointment;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\Availability;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
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
        $admin = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tenant_id' => $tenant->id,
            ]
        );

        // 3. Create Demo Staff Users
        $staff1 = User::firstOrCreate(
            ['email' => 'jane.smith.demo@example.com'],
            [
                'name' => 'Dr. Jane Smith (Demo)',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'phone' => '+1 (555) 014-9988',
                'tenant_id' => $tenant->id,
            ]
        );

        $staff2 = User::firstOrCreate(
            ['email' => 'john.doe.demo@example.com'],
            [
                'name' => 'Dr. John Doe (Demo)',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'phone' => '+1 (555) 012-7766',
                'tenant_id' => $tenant->id,
            ]
        );

        $staff3 = User::firstOrCreate(
            ['email' => 'sarah.j.demo@example.com'],
            [
                'name' => 'Sarah Johnson (Demo)',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'phone' => '+1 (555) 017-3344',
                'tenant_id' => $tenant->id,
            ]
        );

        $staffMembers = [$staff1, $staff2, $staff3];

        // 4. Create Demo Client Users
        $clientNames = [
            'Pepper Potts', 'Lucius Fox', 'Miles Dyson',
            'Alice Vance', 'Peter Gibbons', 'Bob Porter', 'Michael Bolton', 'Samir Nagheenanajar',
            'Milton Waddams', 'Dom Portwood', 'Bill Lumbergh', 'Tom Smykowski', 'Rob Hunter'
        ];
        $clients = [];
        foreach ($clientNames as $index => $name) {
            $email = strtolower(str_replace(' ', '.', $name)) . '.demo@example.com';
            $clients[] = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => 'client',
                    'tenant_id' => $tenant->id,
                ]
            );
        }

        // 5. Create Demo Settings
        Setting::updateOrCreate(['key' => 'app_name', 'tenant_id' => $tenant->id], ['value' => 'Nexa Demo Workspace']);
        Setting::updateOrCreate(['key' => 'business_email', 'tenant_id' => $tenant->id], ['value' => 'demo@nexa.com']);
        Setting::updateOrCreate(['key' => 'business_hours', 'tenant_id' => $tenant->id], ['value' => '09:00 - 18:00']);
        Setting::updateOrCreate(['key' => 'booking_interval', 'tenant_id' => $tenant->id], ['value' => '30 mins']);
        Setting::updateOrCreate(['key' => 'enable_notifications', 'tenant_id' => $tenant->id], ['value' => 'true']);

        // 6. Create Demo Availabilities
        foreach ($staffMembers as $index => $staff) {
            $timezones = ['America/New_York', 'Europe/London', 'Asia/Singapore'];
            Availability::updateOrCreate(
                ['user_id' => $staff->id, 'tenant_id' => $tenant->id],
                [
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
                ]
            );
        }

        // 7. Create Demo B2B CRM Data (Companies, Contacts, Deals, Activities)
        $companiesData = [
            ['name' => 'Stark Industries (Demo)', 'industry' => 'Aerospace', 'website' => 'https://stark.demo', 'revenue' => '$90M', 'employee_count' => 850, 'tenant_id' => $tenant->id],
            ['name' => 'Wayne Enterprises (Demo)', 'industry' => 'Defense', 'website' => 'https://wayne.demo', 'revenue' => '$150M', 'employee_count' => 1200, 'tenant_id' => $tenant->id],
            ['name' => 'Cyberdyne Systems (Demo)', 'industry' => 'AI & Robotics', 'website' => 'https://cyberdyne.demo', 'revenue' => '$35M', 'employee_count' => 240, 'tenant_id' => $tenant->id],
            ['name' => 'Tyrell Corporation (Demo)', 'industry' => 'Biotech', 'website' => 'https://tyrell.demo', 'revenue' => '$50M', 'employee_count' => 450, 'tenant_id' => $tenant->id],
        ];

        $seededCompanies = [];
        foreach ($companiesData as $companyData) {
            $seededCompanies[] = Company::create($companyData);
        }

        $contactsData = [
            ['company_id' => $seededCompanies[0]->id, 'name' => 'Pepper Potts', 'position' => 'CEO', 'email' => 'pepper@stark.demo', 'phone' => '+1 (555) 193-2940', 'tenant_id' => $tenant->id],
            ['company_id' => $seededCompanies[1]->id, 'name' => 'Lucius Fox', 'position' => 'Business Manager', 'email' => 'lucius@wayne.demo', 'phone' => '+1 (555) 830-4921', 'tenant_id' => $tenant->id],
            ['company_id' => $seededCompanies[2]->id, 'name' => 'Miles Dyson', 'position' => 'Lead Architect', 'email' => 'miles@cyberdyne.demo', 'phone' => '+1 (555) 749-0129', 'tenant_id' => $tenant->id],
            ['company_id' => $seededCompanies[3]->id, 'name' => 'Rachael Deckard', 'position' => 'Relations Director', 'email' => 'rachael@tyrell.demo', 'phone' => '+1 (555) 482-9018', 'tenant_id' => $tenant->id],
        ];

        $seededContacts = [];
        foreach ($contactsData as $contactData) {
            $seededContacts[] = Contact::create($contactData);
        }

        $dealsData = [
            ['title' => 'Stark Arc Reactor Integration', 'company_id' => $seededCompanies[0]->id, 'contact_id' => $seededContacts[0]->id, 'value' => 85000.00, 'stage' => 'interested', 'score' => 90, 'tenant_id' => $tenant->id],
            ['title' => 'Wayne Tactical Gear Licensing', 'company_id' => $seededCompanies[1]->id, 'contact_id' => $seededContacts[1]->id, 'value' => 120000.00, 'stage' => 'contacted', 'score' => 75, 'tenant_id' => $tenant->id],
            ['title' => 'Cyberdyne CPU Architecture Sync', 'company_id' => $seededCompanies[2]->id, 'contact_id' => $seededContacts[2]->id, 'value' => 45000.00, 'stage' => 'booked', 'score' => 95, 'tenant_id' => $tenant->id],
            ['title' => 'Tyrell Replicant Logistics', 'company_id' => $seededCompanies[3]->id, 'contact_id' => $seededContacts[3]->id, 'value' => 60000.00, 'stage' => 'cold', 'score' => 45, 'tenant_id' => $tenant->id],
        ];

        foreach ($dealsData as $deal) {
            Deal::create($deal);
        }

        // 8. Create Demo Appointments
        // Explicitly seed 3 appointments for TODAY to show the workflow in the dashboard
        Appointment::create([
            'client_id' => $clients[0]->id, // Pepper Potts
            'staff_id' => $staff1->id, // Dr. Jane Smith
            'title' => 'Stark Arc Reactor Integration Sync',
            'start_time' => now()->setHour(10)->setMinute(0)->setSecond(0),
            'end_time' => now()->setHour(10)->setMinute(30)->setSecond(0),
            'status' => 'confirmed',
            'note' => 'Discovery session for Stark Arc Reactor Integration. Pepper Potts requested deep CRM timeline sync alignment details.',
            'calendar_provider' => 'google',
            'google_event_id' => 'mock-google-event-today-1',
            'meeting_link' => 'https://meet.google.com/nexa-demo-stark',
            'tenant_id' => $tenant->id,
        ]);

        Appointment::create([
            'client_id' => $clients[1]->id, // Lucius Fox
            'staff_id' => $staff2->id, // Dr. John Doe
            'title' => 'Wayne Tactical Gear Licensing Review',
            'start_time' => now()->setHour(13)->setMinute(0)->setSecond(0),
            'end_time' => now()->setHour(13)->setMinute(30)->setSecond(0),
            'status' => 'scheduled',
            'note' => 'API review with Lucius Fox regarding tactical gear licensing sync endpoints.',
            'calendar_provider' => 'google',
            'google_event_id' => 'mock-google-event-today-2',
            'meeting_link' => 'https://meet.google.com/nexa-demo-wayne',
            'tenant_id' => $tenant->id,
        ]);

        Appointment::create([
            'client_id' => $clients[2]->id, // Miles Dyson
            'staff_id' => $staff3->id, // Sarah Johnson
            'title' => 'Cyberdyne CPU Architecture Sync',
            'start_time' => now()->setHour(15)->setMinute(30)->setSecond(0),
            'end_time' => now()->setHour(16)->setMinute(0)->setSecond(0),
            'status' => 'scheduled',
            'note' => 'Sales alignment for Cyberdyne systems CPU architecture interface integrations.',
            'calendar_provider' => 'google',
            'google_event_id' => 'mock-google-event-today-3',
            'meeting_link' => 'https://meet.google.com/nexa-demo-cyberdyne',
            'tenant_id' => $tenant->id,
        ]);

        // Seed 7 other random appointments with non-zero offsets
        $services = ['General Consultation', 'Integrations Discovery', 'API Review Meeting', 'Sales Scope Sync'];
        $statuses = ['scheduled', 'confirmed', 'completed'];

        for ($i = 0; $i < 7; $i++) {
            $client = $clients[array_rand($clients)];
            $staff = $staffMembers[array_rand($staffMembers)];
            $status = $statuses[array_rand($statuses)];
            $title = $services[array_rand($services)];
            
            // Random date in current month (not today)
            $daysOffset = 0;
            while ($daysOffset === 0) {
                $daysOffset = rand(-10, 10);
            }
            $startHour = rand(9, 16);
            $startTime = now()->addDays($daysOffset)->setHour($startHour)->setMinute(0)->setSecond(0);
            $endTime = (clone $startTime)->addMinutes(30);

            Appointment::create([
                'client_id' => $client->id,
                'staff_id' => $staff->id,
                'title' => $title,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status,
                'note' => 'Demo appointment ' . ($i + 1),
                'calendar_provider' => 'google',
                'google_event_id' => 'mock-google-event-demo-' . $i,
                'meeting_link' => 'https://meet.google.com/nexa-demo-mock-' . $i,
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
