<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed tenant and provider
        $tenant = Tenant::create(['name' => 'Test Tenant', 'slug' => 'test-tenant']);
        
        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'tenant_id' => $tenant->id,
        ]);

        User::create([
            'name' => 'John Client',
            'email' => 'john.client@example.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_can_retrieve_provider_details_unauthenticated()
    {
        $response = $this->getJson('/api/public/booking/jane-smith');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'name',
                     'email',
                     'timezone',
                     'working_hours'
                 ])
                 ->assertJsonPath('name', 'Jane Smith');
    }

    public function test_can_retrieve_available_slots_unauthenticated()
    {
        $response = $this->getJson('/api/public/booking/jane-smith/slots?date=' . now()->next('Monday')->format('Y-m-d'));

        $response->assertStatus(200);
    }

    public function test_can_book_appointment_unauthenticated()
    {
        $provider = User::where('role', 'staff')->first();
        $monday = now()->next('Monday');
        
        $response = $this->postJson('/api/public/book', [
            'provider_id' => $provider->id,
            'start_time' => $monday->copy()->setHour(10)->setMinute(0)->toIso8601String(),
            'end_time' => $monday->copy()->setHour(10)->setMinute(30)->toIso8601String(),
            'client_name' => 'New B2B Lead',
            'client_email' => 'lead@company.com',
            'notes' => 'Looking to discuss enterprise integrations.'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'appointment',
                     'deal',
                     'company'
                 ]);
    }

    public function test_can_book_with_email_already_registered_in_different_tenant()
    {
        $tenant2 = Tenant::create(['name' => 'Second Tenant', 'slug' => 'second-tenant']);
        
        $provider2 = User::create([
            'name' => 'Bob Provider',
            'email' => 'bob@secondtenant.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'tenant_id' => $tenant2->id,
        ]);

        $monday = now()->next('Monday');

        $response = $this->withSession(['tenant_id' => $tenant2->id])
            ->postJson('/api/public/book', [
                'provider_id' => $provider2->id,
                'start_time' => $monday->copy()->setHour(11)->setMinute(0)->toIso8601String(),
                'end_time' => $monday->copy()->setHour(11)->setMinute(30)->toIso8601String(),
                'client_name' => 'John Client',
                'client_email' => 'john.client@example.com',
                'notes' => 'Booking under tenant 2.'
            ]);

        $response->assertStatus(201);

        $this->assertEquals(2, User::withoutGlobalScope('tenant_scope')->where('email', 'john.client@example.com')->count());
    }
}
