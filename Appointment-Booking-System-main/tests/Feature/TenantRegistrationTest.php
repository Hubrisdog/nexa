<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_new_business_tenant_and_admin()
    {
        $response = $this->postJson('/api/register', [
            'company_name' => 'Acme Consulting Group',
            'slug' => 'acme-consulting',
            'name' => 'John Boss',
            'email' => 'john.boss@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1 (555) 999-8888'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => [
                         'id',
                         'name',
                         'email',
                         'role',
                         'tenant_id'
                     ],
                     'message' => []
                 ])
                 ->assertJsonPath('user.role', 'admin');

        // Verify database records
        $tenant = Tenant::where('slug', 'acme-consulting')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Acme Consulting Group', $tenant->name);

        $user = User::where('email', 'john.boss@acme.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($tenant->id, $user->tenant_id);
        $this->assertEquals('admin', $user->role);

        // Verify default settings seeded
        $this->assertDatabaseHas('settings', [
            'key' => 'app_name',
            'value' => 'Acme Consulting Group',
            'tenant_id' => $tenant->id
        ]);
    }

    public function test_cannot_register_tenant_with_duplicate_slug()
    {
        Tenant::create([
            'name' => 'Existing Company',
            'slug' => 'duplicate-slug',
            'plan' => 'free'
        ]);

        $response = $this->postJson('/api/register', [
            'company_name' => 'New Company',
            'slug' => 'duplicate-slug',
            'name' => 'Jane Boss',
            'email' => 'jane.boss@company.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['slug']);
    }
}
