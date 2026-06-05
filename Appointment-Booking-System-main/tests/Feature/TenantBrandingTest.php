<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant1;
    protected $tenant2;
    protected $staff1;
    protected $staff2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Tenant::create([
            'name' => 'Stark Industries',
            'slug' => 'stark',
            'plan' => 'pro',
            'brand_color' => '#ef4444',
            'logo_path' => 'uploads/logos/stark.png'
        ]);

        $this->tenant2 = Tenant::create([
            'name' => 'Wayne Enterprises',
            'slug' => 'wayne',
            'plan' => 'enterprise',
            'custom_domain' => 'book.wayne.com',
            'brand_color' => '#0f172a'
        ]);

        $this->staff1 = User::create([
            'name' => 'Pepper Potts',
            'email' => 'pepper@stark.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'tenant_id' => $this->tenant1->id
        ]);

        $this->staff2 = User::create([
            'name' => 'Lucius Fox',
            'email' => 'lucius@wayne.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'tenant_id' => $this->tenant2->id
        ]);
    }

    public function test_subdomain_resolves_tenant_correctly()
    {
        // Visit stark.localhost
        $response = $this->getJson('http://stark.localhost/api/public/workspace');

        $response->assertStatus(200)
                 ->assertJsonPath('tenant.name', 'Stark Industries')
                 ->assertJsonPath('tenant.brand_color', '#ef4444')
                 ->assertJsonCount(1, 'providers')
                 ->assertJsonPath('providers.0.name', 'Pepper Potts');
    }

    public function test_custom_domain_resolves_tenant_correctly()
    {
        // Visit book.wayne.com
        $response = $this->getJson('http://book.wayne.com/api/public/workspace');

        $response->assertStatus(200)
                 ->assertJsonPath('tenant.name', 'Wayne Enterprises')
                 ->assertJsonPath('tenant.brand_color', '#0f172a')
                 ->assertJsonCount(1, 'providers')
                 ->assertJsonPath('providers.0.name', 'Lucius Fox');
    }

    public function test_missing_subdomain_returns_workspace_not_found()
    {
        // Visit localhost (no active tenant matches)
        $response = $this->getJson('http://localhost/api/public/workspace');

        $response->assertStatus(404);
    }

    public function test_can_update_branding_settings_including_logo()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $logo = \Illuminate\Http\UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($this->staff1)
            ->postJson('/api/settings/branding', [
                'name' => 'Stark Industries V2',
                'brand_color' => '#ff0000',
                'custom_domain' => 'book.stark.com',
                'custom_email_footer' => 'Sent from Stark Tower',
                'logo' => $logo
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('tenant.name', 'Stark Industries V2')
                 ->assertJsonPath('tenant.brand_color', '#ff0000')
                 ->assertJsonPath('tenant.custom_domain', 'book.stark.com');

        $this->tenant1->refresh();
        $this->assertEquals('Stark Industries V2', $this->tenant1->name);
        $this->assertEquals('#ff0000', $this->tenant1->brand_color);
        $this->assertEquals('book.stark.com', $this->tenant1->custom_domain);
        $this->assertEquals('Sent from Stark Tower', $this->tenant1->custom_email_footer);
        $this->assertNotNull($this->tenant1->logo_path);

        $this->assertDatabaseHas('settings', [
            'key' => 'app_name',
            'value' => 'Stark Industries V2',
            'tenant_id' => $this->tenant1->id
        ]);
        
        // Clean up mock uploaded file
        if (file_exists(public_path($this->tenant1->logo_path))) {
            @unlink(public_path($this->tenant1->logo_path));
        }
    }
}
