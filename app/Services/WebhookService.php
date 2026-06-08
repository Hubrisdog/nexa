<?php

namespace App\Services;

use App\Models\WebhookSubscription;
use App\Jobs\DispatchWebhookJob;

class WebhookService
{
    /**
     * Dispatch a webhook event for a specific tenant.
     *
     * @param string $event
     * @param array $payload
     * @param int $tenantId
     * @return void
     */
    public function dispatch($event, array $payload, $tenantId)
    {
        $subscriptions = WebhookSubscription::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            if (is_array($subscription->events) && in_array($event, $subscription->events)) {
                DispatchWebhookJob::dispatch($subscription->id, $event, $payload);
            }
        }
    }
}
