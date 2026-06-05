<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $isEnabled;
    protected $token;
    protected $phoneNumberId;

    public function __construct()
    {
        $this->token = env('WHATSAPP_API_TOKEN');
        $this->phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        $this->isEnabled = !empty($this->token) && !empty($this->phoneNumberId);
    }

    /**
     * Send a WhatsApp text notification (supporting live or mock modes).
     */
    public function sendMessage($recipient, $body)
    {
        if (!$this->isEnabled) {
            Log::info("WhatsApp Outbox [MOCK]: Recipient '{$recipient}' successfully notified. Content: \"{$body}\"");
            return [
                'success' => true,
                'mock' => true,
            ];
        }

        try {
            $url = "https://graph.facebook.com/v16.0/{$this->phoneNumberId}/messages";
            $response = Http::withToken($this->token)->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => [
                    'body' => $body
                ]
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp Outbox [LIVE]: Notification dispatched to '{$recipient}' successfully.");
                return [
                    'success' => true,
                    'message_id' => $response->json()['messages'][0]['id'] ?? null
                ];
            }

            Log::error("WhatsApp API Error: " . $response->body());
        } catch (\Exception $e) {
            Log::error("WhatsApp Service Exception: " . $e->getMessage());
        }

        return ['success' => false];
    }
}
