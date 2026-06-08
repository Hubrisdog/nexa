<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopingValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenantA;
    protected $tenantB;
    protected $adminA;
    protected $adminB;
    protected $companyB;
    protected $contactB;
    protected $clientB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        $this->adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin.a@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin.b@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'tenant_id' => $this->tenantB->id,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B',
            'tenant_id' => $this->tenantB->id
        ]);

        $this->contactB = Contact::create([
            'name' => 'Contact B',
            'company_id' => $this->companyB->id,
            'tenant_id' => $this->tenantB->id
        ]);

        $this->clientB = User::create([
            'name' => 'Client B',
            'email' => 'client.b@example.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'tenant_id' => $this->tenantB->id
        ]);
    }

    public function test_cannot_assign_company_from_different_tenant_to_deal()
    {
        $response = $this->actingAs($this->adminA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->postJson('/api/crm/deals', [
                'title' => 'Cross Tenant Deal',
                'company_id' => $this->companyB->id,
                'value' => 5000,
                'stage' => 'cold'
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['company_id']);
    }

    public function test_cannot_assign_contact_from_different_tenant_to_deal()
    {
        $response = $this->actingAs($this->adminA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->postJson('/api/crm/deals', [
                'title' => 'Cross Tenant Deal',
                'contact_id' => $this->contactB->id,
                'value' => 5000,
                'stage' => 'cold'
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['contact_id']);
    }

    public function test_cannot_assign_company_from_different_tenant_to_contact()
    {
        $response = $this->actingAs($this->adminA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->postJson('/api/crm/contacts', [
                'name' => 'New Contact',
                'company_id' => $this->companyB->id,
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['company_id']);
    }

    public function test_cannot_create_appointment_with_client_from_different_tenant()
    {
        $response = $this->actingAs($this->adminA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->postJson('/api/appointments', [
                'client_id' => $this->clientB->id,
                'staff_id' => $this->adminA->id,
                'title' => 'Cross Tenant Appointment',
                'start_time' => now()->next('Monday')->setHour(10)->toIso8601String(),
                'end_time' => now()->next('Monday')->setHour(10)->addMinutes(30)->toIso8601String(),
                'status' => 'scheduled'
            ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client_id']);
    }
}
