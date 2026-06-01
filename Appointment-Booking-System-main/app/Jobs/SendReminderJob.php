<?php

namespace App\Jobs;

use App\Models\SequenceLog;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $logId;

    /**
     * Create a new job instance.
     */
    public function __construct($logId)
    {
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService)
    {
        $seqLog = SequenceLog::with(['sequence', 'step', 'appointment.client', 'appointment.staff'])->find($this->logId);

        if (!$seqLog) {
            Log::warning("SendReminderJob: Log ID {$this->logId} not found.");
            return;
        }

        // Exclude cancelled appointments from alerts
        if ($seqLog->appointment && $seqLog->appointment->status === 'cancelled') {
            $seqLog->update(['status' => 'failed']);
            Log::info("SendReminderJob: Aborted for cancelled Appointment #{$seqLog->appointment_id}.");
            return;
        }

        $clientName = $seqLog->appointment->client->name ?? 'Client';
        $staffName = $seqLog->appointment->staff->name ?? 'Staff';
        $startTime = $seqLog->appointment ? $seqLog->appointment->start_time->format('F j, Y, g:i a') : '';
        $meetingLink = $seqLog->appointment->meeting_link ?? 'N/A';

        // Parse placeholders in body template
        $body = $seqLog->step->body;
        $body = str_replace('{client_name}', $clientName, $body);
        $body = str_replace('{staff_name}', $staffName, $body);
        $body = str_replace('{start_time}', $startTime, $body);
        $body = str_replace('{meeting_link}', $meetingLink, $body);

        $subject = $seqLog->step->subject;
        if ($subject) {
            $subject = str_replace('{client_name}', $clientName, $subject);
            $subject = str_replace('{start_time}', $startTime, $subject);
        }

        $recipient = $seqLog->recipient;
        $type = $seqLog->step->type;

        Log::info("Executing Reminder Step #{$seqLog->step_id} ({$type}) for Recipient: {$recipient}");

        if ($type === 'email') {
            Log::info("Email Outbox [MOCK]: Recipient '{$recipient}' successfully notified.\nSubject: {$subject}\nContent: \"{$body}\"");
            $seqLog->update(['status' => 'sent', 'sent_at' => now()]);
        } elseif ($type === 'sms') {
            Log::info("SMS Outbox [MOCK]: Recipient '{$recipient}' successfully notified. Content: \"{$body}\"");
            $seqLog->update(['status' => 'sent', 'sent_at' => now()]);
        } elseif ($type === 'whatsapp') {
            $result = $whatsAppService->sendMessage($recipient, $body);
            if ($result['success']) {
                $seqLog->update(['status' => 'sent', 'sent_at' => now()]);
            } else {
                $seqLog->update(['status' => 'failed']);
            }
        }
    }
}
