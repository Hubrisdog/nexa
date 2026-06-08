<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use App\Models\Deal;
use App\Jobs\DispatchWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Acme Corp', 'slug' => 'acme']);
        
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@acme.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_manage_webhook_subscriptions()
    {
        $this->actingAs($this->admin);

        // 1. Create Subscription
        $response = $this->postJson('/api/webhooks', [
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_secretkey',
            'events' => ['appointment.created'],
            'is_active' => true,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('subscription.url', 'https://example.com/webhook');

        $this->assertDatabaseHas('webhook_subscriptions', [
            'url' => 'https://example.com/webhook',
            'tenant_id' => $this->tenant->id,
        ]);

        $subscriptionId = $response->json('subscription.id');

        // 2. List Subscriptions
        $response = $this->getJson('/api/webhooks');
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'subscriptions');

        // 3. Update Subscription
        $response = $this->putJson("/api/webhooks/{$subscriptionId}", [
            'url' => 'https://example.com/webhook-updated',
            'secret' => 'whsec_secretkey_updated',
            'events' => ['appointment.created', 'deal.updated'],
            'is_active' => false,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('webhook_subscriptions', [
            'id' => $subscriptionId,
            'url' => 'https://example.com/webhook-updated',
            'is_active' => false,
        ]);

        // 4. Delete Subscription
        $response = $this->deleteJson("/api/webhooks/{$subscriptionId}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('webhook_subscriptions', ['id' => $subscriptionId]);
    }

    public function test_webhook_dispatches_job_on_appointment_creation()
    {
        Queue::fake();

        $subscription = WebhookSubscription::create([
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_secret',
            'events' => ['appointment.created'],
            'is_active' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $monday = now()->next('Monday');
        $response = $this->withSession(['tenant_id' => $this->tenant->id])
            ->postJson('/api/public/book', [
                'provider_id' => $this->admin->id,
                'start_time' => $monday->copy()->setHour(10)->setMinute(0)->toIso8601String(),
                'end_time' => $monday->copy()->setHour(10)->setMinute(30)->toIso8601String(),
                'client_name' => 'John Webhook Test',
                'client_email' => 'webhook@example.com',
                'notes' => 'Test scheduling triggers outbound webhook.'
            ]);

        $response->assertStatus(201);

        Queue::assertPushed(DispatchWebhookJob::class);
    }

    public function test_webhook_job_computes_signature_and_logs_delivery()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('OK', 200),
        ]);

        $subscription = WebhookSubscription::create([
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_secret',
            'events' => ['deal.updated'],
            'is_active' => true,
            'tenant_id' => $this->tenant->id,
        ]);

        $payload = ['foo' => 'bar'];
        $job = new DispatchWebhookJob($subscription->id, 'deal.updated', $payload);
        $job->handle();

        Http::assertSent(function ($request) use ($subscription, $payload) {
            $expectedSignature = hash_hmac('sha256', json_encode($payload), $subscription->secret);
            return $request->url() === 'https://example.com/webhook'
                && $request->header('X-Nexa-Signature')[0] === $expectedSignature
                && $request->header('X-Nexa-Event')[0] === 'deal.updated';
        });

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_subscription_id' => $subscription->id,
            'event' => 'deal.updated',
            'response_status' => 200,
        ]);
    }
}
