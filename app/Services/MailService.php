<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\Demo;

class MailService
{
    protected $apiKey;
    protected $fromEmail;
    protected $provider; // 'resend', 'mailgun', or 'log'

    public function __construct()
    {
        $this->apiKey = env('TRANSACTIONAL_MAIL_API_KEY');
        $this->fromEmail = env('TRANSACTIONAL_MAIL_FROM', 'noreply@nexa.com');
        $this->provider = strtolower(env('TRANSACTIONAL_MAIL_PROVIDER', 'log'));
    }

    /**
     * Sends booking confirmation.
     */
    public function sendBookingConfirmation(Appointment $appointment): bool
    {
        $subject = "Confirmed: Discovery Sync - {$appointment->title}";
        $body = "Hi {$appointment->client->name},\r\n\r\n" .
                "Your meeting is confirmed. Details:\r\n" .
                "- Host: {$appointment->staff->name}\r\n" .
                "- Time: {$appointment->start_time} (UTC)\r\n" .
                "- Meet Link: " . ($appointment->meeting_link ?? 'Pending') . "\r\n\r\n" .
                "Thanks,\r\nNexa Automation";

        return $this->send($appointment->client->email, $subject, $body);
    }

    /**
     * Sends reschedule alert.
     */
    public function sendReschedule(Appointment $appointment, $oldTime): bool
    {
        $subject = "Rescheduled: Discovery Sync - {$appointment->title}";
        $body = "Hi {$appointment->client->name},\r\n\r\n" .
                "Your meeting was rescheduled.\r\n" .
                "- Old Time: {$oldTime}\r\n" .
                "- New Time: {$appointment->start_time} (UTC)\r\n" .
                "- Meet Link: " . ($appointment->meeting_link ?? 'Pending') . "\r\n\r\n" .
                "Thanks,\r\nNexa Automation";

        return $this->send($appointment->client->email, $subject, $body);
    }

    /**
     * Sends cancellation alert.
     */
    public function sendCancellation(Appointment $appointment): bool
    {
        $subject = "Canceled: Discovery Sync - {$appointment->title}";
        $body = "Hi {$appointment->client->name},\r\n\r\n" .
                "Your meeting has been canceled.\r\n" .
                "If you need to select another time slot, please visit our booking page.\r\n\r\n" .
                "Thanks,\r\nNexa Automation";

        return $this->send($appointment->client->email, $subject, $body);
    }

    /**
     * Send email using selected provider.
     */
    protected function send(string $to, string $subject, string $body): bool
    {
        if (Demo::active()) {
            return Demo::mock('mail_send', compact('to', 'subject', 'body'));
        }

        if (empty($this->apiKey) || $this->provider === 'log') {
            Log::info("--- TRANSACTIONAL EMAIL OUTBOX (MOCK) ---\n" .
                     "To: {$to}\n" .
                     "From: {$this->fromEmail}\n" .
                     "Subject: {$subject}\n" .
                     "Body:\n{$body}\n" .
                     "-----------------------------------------");
            return true;
        }

        try {
            if ($this->provider === 'resend') {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])->post('https://api.resend.com/emails', [
                    'from' => $this->fromEmail,
                    'to' => $to,
                    'subject' => $subject,
                    'text' => $body,
                ]);

                if ($response->successful()) {
                    Log::info("Resend Email successfully sent to {$to}.");
                    return true;
                }
                Log::error("Resend API Error: " . $response->body());
            } elseif ($this->provider === 'mailgun') {
                $domain = env('TRANSACTIONAL_MAIL_DOMAIN');
                $response = Http::asForm()
                    ->withBasicAuth('api', $this->apiKey)
                    ->post("https://api.mailgun.net/v3/{$domain}/messages", [
                        'from' => $this->fromEmail,
                        'to' => $to,
                        'subject' => $subject,
                        'text' => $body,
                    ]);

                if ($response->successful()) {
                    Log::info("Mailgun Email successfully sent to {$to}.");
                    return true;
                }
                Log::error("Mailgun API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("MailService Send Exception: " . $e->getMessage());
        }

        return false;
    }
}
