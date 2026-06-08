<?php

namespace App\Helpers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class Demo
{
    /**
     * Checks if Demo Mode is active.
     * Returns true if DEMO_MODE=true in .env or the current active tenant is a demo tenant.
     */
    public static function active(): bool
    {
        if (env('DEMO_MODE') === true || env('DEMO_MODE') === 'true') {
            return true;
        }

        $tenant = tenant();
        return $tenant && ($tenant->is_demo || $tenant->slug === 'demo');
    }

    /**
     * Standard centralized mock responses for external integrations.
     */
    public static function mock(string $integration, array $parameters = [])
    {
        switch ($integration) {
            case 'google_calendar_sync':
                $appointment = $parameters['appointment'] ?? null;
                $action = $parameters['action'] ?? 'create';
                
                Log::info("Google Calendar Sync [DEMO MOCK]: Action '{$action}' simulated successfully for Appointment #{$appointment?->id} ('{$appointment?->title}').");
                return [
                    'success' => true,
                    'mock' => true,
                    'event_id' => 'mock-google-event-' . ($appointment?->id ?? rand(1000, 9999)),
                    'meeting_link' => "https://meet.google.com/nexa-" . ($appointment?->id ?? rand(1000, 9999)) . "-meet"
                ];

            case 'outlook_calendar_sync':
                $appointment = $parameters['appointment'] ?? null;
                $action = $parameters['action'] ?? 'create';
                
                Log::info("Outlook Calendar Sync [DEMO MOCK]: Action '{$action}' simulated successfully for Appointment #{$appointment?->id} ('{$appointment?->title}').");
                return [
                    'success' => true,
                    'mock' => true,
                    'event_id' => 'mock-outlook-event-' . ($appointment?->id ?? rand(1000, 9999)),
                    'meeting_link' => "https://teams.microsoft.com/l/meetup-join/nexa-" . ($appointment?->id ?? rand(1000, 9999)) . "-teams"
                ];

            case 'mail_send':
                $to = $parameters['to'] ?? '';
                $subject = $parameters['subject'] ?? '';
                $body = $parameters['body'] ?? '';
                
                Log::info("--- TRANSACTIONAL EMAIL (DEMO MOCK) ---\n" .
                         "To: {$to}\n" .
                         "Subject: {$subject}\n" .
                         "Body:\n{$body}\n" .
                         "-----------------------------------------");
                return true;

            case 'whatsapp_send':
                $recipient = $parameters['recipient'] ?? '';
                $body = $parameters['body'] ?? '';
                
                Log::info("WhatsApp Outbox [DEMO MOCK]: Recipient '{$recipient}' simulated. Content: \"{$body}\"");
                return [
                    'success' => true,
                    'mock' => true,
                ];

            case 'ai_score_lead':
                $deal = $parameters['deal'] ?? null;
                $score = 75; // baseline demo score
                
                if ($deal && $deal->value > 50000) {
                    $score = 90;
                } elseif ($deal && $deal->value < 10000) {
                    $score = 45;
                }
                
                Log::info("AI Lead Scoring Engine [DEMO MOCK]: Evaluated deal '{$deal?->title}' (Value: \${$deal?->value}) with score: {$score}%.");
                return $score;

            case 'ai_summarize':
                $appointment = $parameters['appointment'] ?? null;
                
                Log::info("AI Summarizer [DEMO MOCK]: Generated meeting summary for Appointment #{$appointment?->id}.");
                return [
                    'summary' => "The discovery sync regarding \"{$appointment?->title}\" concluded successfully. Key integration requirements were discussed.",
                    'highlights' => [
                        "Identified calendar sync preferences.",
                        "Discussed webhook configurations and pipeline automation steps."
                    ],
                    'action_items' => [
                        "Review api documentation.",
                        "Follow up with technical scope document."
                    ],
                    'mock' => true
                ];

            case 'stripe_subscribe':
                $plan = $parameters['plan'] ?? 'free';
                
                Log::info("Stripe Billing [DEMO MOCK]: Simulated subscription created for plan: " . strtoupper($plan));
                return [
                    'plan' => $plan,
                    'stripe_customer_id' => 'cus_demo_' . uniqid(),
                    'stripe_subscription_id' => 'sub_demo_' . uniqid(),
                    'subscription_status' => 'active'
                ];

            default:
                return null;
        }
    }
}
