<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Helpers\Demo;

class BillingController extends Controller
{
    public function getBillingDetails()
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        if (!$tenant) {
            // Default bootstrap tenant for developer testing if user has none
            $tenant = Tenant::create([
                'name' => 'Default Organization',
                'slug' => 'default-org',
                'plan' => 'free',
            ]);
            $user->update(['tenant_id' => $tenant->id]);
        }

        return response()->json($tenant);
    }

    public function updateSubscription(Request $request)
    {
        $user = auth()->user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return response()->json(['message' => 'Tenant not resolved.'], 404);
        }

        $fields = $request->validate([
            'plan' => ['required', Rule::in(['free', 'pro', 'enterprise'])]
        ]);

        if (Demo::active()) {
            $mockData = Demo::mock('stripe_subscribe', ['plan' => $fields['plan']]);
            $tenant->update([
                'plan' => $mockData['plan'],
                'stripe_customer_id' => $mockData['stripe_customer_id'],
                'stripe_subscription_id' => $mockData['stripe_subscription_id'],
                'subscription_status' => $mockData['subscription_status']
            ]);
            return response()->json($tenant);
        }

        // Simulating Stripe Customer Session or Billing Portal
        $tenant->update([
            'plan' => $fields['plan'],
            'stripe_customer_id' => $tenant->stripe_customer_id ?? 'cus_mock_' . uniqid(),
            'stripe_subscription_id' => $tenant->stripe_subscription_id ?? 'sub_mock_' . uniqid(),
            'subscription_status' => 'active'
        ]);

        Log::info("Billing Subscription [MOCK]: Tenant #{$tenant->id} successfully updated plan to: " . strtoupper($fields['plan']));

        return response()->json($tenant);
    }

    /**
     * Handles live/mock Stripe Webhooks.
     */
    public function stripeWebhook(Request $request)
    {
        if (Demo::active()) {
            return response()->json(['status' => 'success', 'message' => 'Demo mode - webhook ignored.']);
        }

        $payload = $request->all();
        $eventType = $payload['type'] ?? '';

        Log::info("Stripe Webhook received: {$eventType}");

        if ($eventType === 'customer.subscription.updated' || $eventType === 'customer.subscription.deleted') {
            $data = $payload['data']['object'] ?? [];
            $subscriptionId = $data['id'] ?? '';
            $status = $data['status'] ?? 'active';
            $priceId = $data['items']['data'][0]['price']['id'] ?? '';

            // Find tenant by subscription id
            $tenant = Tenant::where('stripe_subscription_id', $subscriptionId)->first();
            if ($tenant) {
                $plan = 'free';
                if ($priceId === 'price_pro_plan') $plan = 'pro';
                if ($priceId === 'price_ent_plan') $plan = 'enterprise';

                $tenant->update([
                    'plan' => $plan,
                    'subscription_status' => $status === 'canceled' ? 'inactive' : $status
                ]);

                Log::info("Stripe Webhook [SUCCESS]: Updated Tenant #{$tenant->id} subscription status to '{$status}'");
            }
        }

        return response()->json(['status' => 'success']);
    }
}
