<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\SequenceLog;
use App\Jobs\SendReminderJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SequenceService
{
    /**
     * Triggers appointment-related sequences (confirmation immediately, reminders before).
     */
    public function triggerAppointmentSequences(Appointment $appointment)
    {
        // Try to fetch or create a default sequence
        $sequence = Sequence::where('trigger_type', 'appointment_scheduled')->first();

        if (!$sequence) {
            $sequence = Sequence::create([
                'name' => 'Default Appointment Notifications & Reminders',
                'trigger_type' => 'appointment_scheduled',
            ]);

            // Add immediate confirmation email step
            SequenceStep::create([
                'sequence_id' => $sequence->id,
                'delay_hours' => 0,
                'type' => 'email',
                'subject' => 'Confirmed: {client_name} - Nexa Booking',
                'body' => "Hi {client_name},\n\nYour appointment is confirmed with {staff_name} on {start_time}.\nJoin details: {meeting_link}\n\nBest,\nNexa Team"
            ]);

            // Add 24h before WhatsApp reminder step
            SequenceStep::create([
                'sequence_id' => $sequence->id,
                'delay_hours' => -24,
                'type' => 'whatsapp',
                'body' => "Hi {client_name}, this is a quick reminder for your upcoming session with {staff_name} scheduled for tomorrow, {start_time}. Meeting Link: {meeting_link}"
            ]);
        }

        foreach ($sequence->steps as $step) {
            $scheduledFor = null;

            if ($step->delay_hours < 0) {
                // Negative delay means hours before the appointment start
                $scheduledFor = Carbon::parse($appointment->start_time)->addHours($step->delay_hours);
            } else {
                // Positive delay means hours after scheduling (now)
                $scheduledFor = now()->addHours($step->delay_hours);
            }

            // If the calculated time is in the past (e.g. appointment is scheduled in less than 24 hours), send it immediately
            if ($scheduledFor->lt(now())) {
                $scheduledFor = now();
            }

            $recipient = ($step->type === 'email') ? $appointment->client->email : ($appointment->client->phone ?? '+1234567890');

            if (empty($recipient)) {
                Log::warning("SequenceService: Missing recipient info for user #{$appointment->client_id}, skipping step.");
                continue;
            }

            $log = SequenceLog::create([
                'sequence_id' => $sequence->id,
                'step_id' => $step->id,
                'appointment_id' => $appointment->id,
                'recipient' => $recipient,
                'status' => 'pending',
                'scheduled_for' => $scheduledFor
            ]);

            // Dispatch job to run at the scheduled time
            $delaySeconds = $scheduledFor->diffInSeconds(now());
            if ($delaySeconds > 0) {
                SendReminderJob::dispatch($log->id)->delay($delaySeconds);
            } else {
                SendReminderJob::dispatch($log->id);
            }
        }
    }
}
