<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCalendarConnection;
use App\Models\Tenant;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Labs',
            'slug' => 'acme-labs',
            'plan' => 'free'
        ]);

        $this->admin = User::create([
            'name' => 'Jane Admin',
            'email' => 'jane@acme.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_google_oauth_redirect_runs_mock_when_env_empty()
    {
        // Force empty credentials dynamically
        putenv('GOOGLE_CALENDAR_CLIENT_ID=');
        putenv('GOOGLE_CALENDAR_CLIENT_SECRET=');

        $response = $this->actingAs($this->admin)->get('/api/oauth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('/api/oauth/google/callback', $response->headers->get('Location'));
        $this->assertStringContainsString('mock-google-auth-code', $response->headers->get('Location'));
    }

    public function test_google_oauth_callback_saves_encrypted_connection()
    {
        $response = $this->actingAs($this->admin)->get('/api/oauth/google/callback?code=mock-google-auth-code');

        $response->assertRedirect('/admin/settings?sync=success');

        // Check connection exists
        $connection = UserCalendarConnection::where('user_id', $this->admin->id)
            ->where('provider', 'google')
            ->first();

        $this->assertNotNull($connection);
        $this->assertEquals('primary', $connection->calendar_id);
        $this->assertEquals($this->tenant->id, $connection->tenant_id);

        // Ensure token values are decrypted automatically when read
        $this->assertStringStartsWith('mock-google-access-token', $connection->access_token);
        $this->assertStringStartsWith('mock-google-refresh-token', $connection->refresh_token);

        // Verify that database raw values are encrypted (not plain text)
        $rawConnection = \DB::table('user_calendar_connections')->first();
        $this->assertNotEquals($connection->access_token, $rawConnection->access_token);
        $this->assertStringStartsWith('eyJpdiI6', $rawConnection->access_token); // Laravel default encryption prefix
    }

    public function test_can_disconnect_calendar_connection()
    {
        $connection = UserCalendarConnection::create([
            'user_id' => $this->admin->id,
            'provider' => 'google',
            'email' => 'jane@gmail.com',
            'access_token' => 'some-token',
            'refresh_token' => 'some-refresh',
            'expires_at' => now()->addHour(),
            'calendar_id' => 'primary',
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->actingAs($this->admin)->deleteJson('/api/oauth/connections/google');

        $response->assertStatus(200);
        $this->assertNull(UserCalendarConnection::find($connection->id));
    }

    public function test_get_connections_endpoint_excludes_tokens()
    {
        UserCalendarConnection::create([
            'user_id' => $this->admin->id,
            'provider' => 'google',
            'email' => 'jane@gmail.com',
            'access_token' => 'secret-access-token',
            'refresh_token' => 'secret-refresh-token',
            'expires_at' => now()->addHour(),
            'calendar_id' => 'primary',
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/oauth/connections');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'provider', 'email', 'is_connected', 'created_at']
                 ]);

        // Verify tokens are NOT returned in the connection list json
        $content = $response->getContent();
        $this->assertStringNotContainsString('secret-access-token', $content);
        $this->assertStringNotContainsString('secret-refresh-token', $content);
    }

    public function test_availability_detects_google_calendar_conflict()
    {
        $connection = UserCalendarConnection::create([
            'user_id' => $this->admin->id,
            'provider' => 'google',
            'email' => 'jane@gmail.com',
            'access_token' => 'mock-google-access-token',
            'refresh_token' => 'mock-google-refresh-token',
            'expires_at' => now()->addHour(),
            'calendar_id' => 'primary',
            'tenant_id' => $this->tenant->id
        ]);

        $availabilityService = resolve(AvailabilityService::class);

        // Test normal slot (e.g. 10:00 AM)
        $startOk = Carbon::parse('2026-06-08 10:00:00');
        $endOk = Carbon::parse('2026-06-08 10:30:00');
        $checkOk = $availabilityService->checkAvailability($this->admin, $startOk, $endOk);
        $this->assertTrue($checkOk['available']);

        // Test conflict slot (e.g. 1:00 PM / 13:00 - matches mock logic)
        $startConflict = Carbon::parse('2026-06-08 13:00:00');
        $endConflict = Carbon::parse('2026-06-08 13:30:00');
        $checkConflict = $availabilityService->checkAvailability($this->admin, $startConflict, $endConflict);
        $this->assertFalse($checkConflict['available']);
        $this->assertEquals('Conflicts with an event on your external Google Calendar.', $checkConflict['reason']);
    }
}
