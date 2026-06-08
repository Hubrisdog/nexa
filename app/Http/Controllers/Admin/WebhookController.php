<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookSubscription;
use App\Models\WebhookDelivery;
use App\Jobs\DispatchWebhookJob;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index()
    {
        $subscriptions = WebhookSubscription::latest()->get();
        
        $deliveries = WebhookDelivery::whereIn('webhook_subscription_id', $subscriptions->pluck('id'))
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'subscriptions' => $subscriptions,
            'deliveries' => $deliveries
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:255'],
            'secret' => ['required', 'string', 'max:255'],
            'events' => ['required', 'array'],
            'events.*' => ['required', 'string', 'in:appointment.created,deal.updated'],
            'is_active' => ['required', 'boolean'],
        ]);

        $subscription = WebhookSubscription::create([
            'url' => $data['url'],
            'secret' => $data['secret'],
            'events' => $data['events'],
            'is_active' => $data['is_active'],
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return response()->json([
            'subscription' => $subscription,
            'message' => 'Webhook subscription created successfully'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $subscription = WebhookSubscription::findOrFail($id);

        $data = $request->validate([
            'url' => ['required', 'url', 'max:255'],
            'secret' => ['required', 'string', 'max:255'],
            'events' => ['required', 'array'],
            'events.*' => ['required', 'string', 'in:appointment.created,deal.updated'],
            'is_active' => ['required', 'boolean'],
        ]);

        $subscription->update($data);

        return response()->json([
            'subscription' => $subscription,
            'message' => 'Webhook subscription updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'message' => 'Webhook subscription deleted successfully'
        ]);
    }

    public function testWebhook(Request $request, $id)
    {
        $subscription = WebhookSubscription::findOrFail($id);
        
        $payload = [
            'event' => 'test.ping',
            'timestamp' => now()->toIso8601String(),
            'message' => 'This is a test ping from Nexa.',
            'tenant_id' => $subscription->tenant_id,
        ];

        DispatchWebhookJob::dispatch($subscription->id, 'test.ping', $payload);

        return response()->json([
            'message' => 'Test webhook queued successfully'
        ]);
    }
}
