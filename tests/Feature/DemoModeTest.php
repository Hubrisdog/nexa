<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Company;
use App\Helpers\Demo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the /demo route logs the user in and redirects.
     */
    public function test_demo_route_auto_logs_in_and_redirects(): void
    {
        $response = $this->get('/demo');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/dashboard');

        $this->assertTrue(Auth::check());
        $user = Auth::user();
        $this->assertEquals('demo@example.com', $user->email);
        $this->assertTrue($user->tenant->is_demo);
        $this->assertEquals('demo', $user->tenant->slug);
    }

    /**
     * Test Demo helper active status check.
     */
    public function test_demo_helper_active_status(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Tenant',
            'slug' => 'demo',
            'is_demo' => true,
        ]);

        $user = User::create([
            'name' => 'Demo User',
            'email' => 'test.demo@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $this->assertTrue(Demo::active());
    }

    /**
     * Test reset demo data endpoint wipes and reseeds data.
     */
    public function test_reset_demo_data_endpoint_resets_workspace(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Tenant',
            'slug' => 'demo',
            'is_demo' => true,
        ]);

        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);

        // Add a mock company to demo workspace to verify it gets wiped and reseeded
        Company::create([
            'name' => 'Wipe Me Ltd',
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/demo/reset');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify "Wipe Me Ltd" is gone, and standard seeded companies are now present
        $this->assertDatabaseMissing('companies', [
            'name' => 'Wipe Me Ltd',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Stark Industries (Demo)',
            'tenant_id' => $tenant->id,
        ]);
    }
}
