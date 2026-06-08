<?php

namespace App\Jobs;

use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriptionId;
    protected $event;
    protected $payload;

    public $tries = 3;
    public $backoff = [10, 60, 300]; // Retry after 10s, 1m, 5m

    /**
     * Create a new job instance.
     */
    public function __construct($subscriptionId, $event, array $payload)
    {
        $this->subscriptionId = $subscriptionId;
        $this->event = $event;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $subscription = WebhookSubscription::find($this->subscriptionId);
        if (!$subscription || !$subscription->is_active) {
            return;
        }

        $payloadJson = json_encode($this->payload);
        $signature = hash_hmac('sha256', $payloadJson, $subscription->secret);

        $startTime = microtime(true);
        $status = null;
        $responseBody = null;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Nexa-Event' => $this->event,
                'X-Nexa-Signature' => $signature,
            ])->timeout(10)->post($subscription->url, $this->payload);

            $status = $response->status();
            $responseBody = substr($response->body(), 0, 65535); // Cap storage size
        } catch (\Exception $e) {
            $responseBody = $e->getMessage();
        }

        $durationMs = round((microtime(true) - $startTime) * 1000);

        WebhookDelivery::create([
            'webhook_subscription_id' => $subscription->id,
            'event' => $this->event,
            'payload' => $this->payload,
            'response_status' => $status,
            'response_body' => $responseBody,
            'duration_ms' => $durationMs,
        ]);

        if (is_null($status) || $status < 200 || $status >= 300) {
            Log::warning("Webhook dispatch failed with status '{$status}' for url: {$subscription->url}");
            throw new \Exception("Webhook failed with status " . ($status ?? 'unknown'));
        }
    }
}
