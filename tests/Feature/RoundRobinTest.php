<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Appointment;
use App\Models\Availability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoundRobinTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $staff1;
    protected $staff2;

    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'RoundRobin Corp', 'slug' => 'round-robin']);

        // Create two staff members
        $this->staff1 = User::create([
            'name' => 'Staff One',
            'email' => 'staff1@example.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->staff2 = User::create([
            'name' => 'Staff Two',
            'email' => 'staff2@example.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'tenant_id' => $this->tenant->id,
        ]);

        // Create a client
        $this->client = User::create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'tenant_id' => $this->tenant->id,
        ]);

        // Setup availability for both
        Availability::create([
            'user_id' => $this->staff1->id,
            'working_hours' => [
                'monday' => ['start' => '09:00', 'end' => '17:00'],
            ],
            'breaks' => [],
            'buffer_time' => 0,
            'timezone' => 'UTC',
            'tenant_id' => $this->tenant->id,
        ]);

        Availability::create([
            'user_id' => $this->staff2->id,
            'working_hours' => [
                'monday' => ['start' => '09:00', 'end' => '17:00'],
            ],
            'breaks' => [],
            'buffer_time' => 0,
            'timezone' => 'UTC',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_team_slots_include_union_of_availabilities()
    {
        $monday = now()->next('Monday');

        // Let's create an appointment for staff1, making him busy at 10:00
        Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff1->id,
            'title' => 'Busy Slot',
            'start_time' => $monday->copy()->setTime(10, 0, 0),
            'end_time' => $monday->copy()->setTime(10, 30, 0),
            'status' => 'scheduled',
            'tenant_id' => $this->tenant->id,
        ]);

        // When we fetch team slots, 10:00 should still be available because staff2 is free
        $response = $this->withSession(['tenant_id' => $this->tenant->id])
            ->getJson("/api/public/booking/team/slots?date=" . $monday->format('Y-m-d'));

        $response->assertStatus(200);
        $slots = $response->json();
        
        $slot10 = collect($slots)->first(function($s) {
            return str_contains($s['start'], '10:00:00');
        });

        $this->assertNotNull($slot10);
        $this->assertTrue($slot10['available'], 'Slot should be available since staff2 is free.');
    }

    public function test_booking_chooses_staff_with_lowest_workload()
    {
        $monday = now()->next('Monday');

        // Give staff1 one appointment on this Monday
        Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff1->id,
            'title' => 'First Appointment',
            'start_time' => $monday->copy()->setTime(9, 0, 0),
            'end_time' => $monday->copy()->setTime(9, 30, 0),
            'status' => 'scheduled',
            'tenant_id' => $this->tenant->id,
        ]);

        // Staff2 has 0 appointments on this Monday.
        // If we book at 11:00 AM using team provider, it should route to Staff2 (lowest workload)
        $response = $this->withSession(['tenant_id' => $this->tenant->id])
            ->postJson('/api/public/book', [
                'provider_id' => 0, // 0 resolves to team
                'start_time' => $monday->copy()->setTime(11, 0, 0)->toIso8601String(),
                'end_time' => $monday->copy()->setTime(11, 30, 0)->toIso8601String(),
                'client_name' => 'Lead Team Booking',
                'client_email' => 'teamlead@corp.com',
                'notes' => 'Routing test'
            ]);

        $response->assertStatus(201);
        
        $appointment = Appointment::where('start_time', $monday->copy()->setTime(11, 0, 0))->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($this->staff2->id, $appointment->staff_id, 'Booking should be assigned to Staff Two (0 workload vs 1).');
    }
}
