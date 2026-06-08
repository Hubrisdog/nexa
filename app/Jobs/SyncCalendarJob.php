<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use App\Services\OutlookCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $appointmentId;
    protected $googleEventId;
    protected $outlookEventId;
    protected $calendarProvider;
    protected $title;
    protected $note;
    protected $startTime;
    protected $endTime;
    protected $action;
    protected $staffId;
    protected $clientId;

    /**
     * Create a new job instance.
     */
    public function __construct($appointment, $action = 'create', $provider = null)
    {
        $this->action = $action;
        
        if ($appointment instanceof Appointment) {
            $this->appointmentId = $appointment->id;
            $this->googleEventId = $appointment->google_event_id;
            $this->outlookEventId = $appointment->outlook_event_id;
            $this->calendarProvider = $provider ?? $appointment->calendar_provider ?? 'google';
            $this->title = $appointment->title;
            $this->note = $appointment->note;
            $this->startTime = $appointment->start_time;
            $this->endTime = $appointment->end_time;
            $this->staffId = $appointment->staff_id;
            $this->clientId = $appointment->client_id;
        } else {
            $this->appointmentId = $appointment['id'] ?? null;
            $this->googleEventId = $appointment['google_event_id'] ?? null;
            $this->outlookEventId = $appointment['outlook_event_id'] ?? null;
            $this->calendarProvider = $provider ?? $appointment['calendar_provider'] ?? 'google';
            $this->title = $appointment['title'] ?? '';
            $this->note = $appointment['note'] ?? '';
            $this->startTime = $appointment['start_time'] ?? null;
            $this->endTime = $appointment['end_time'] ?? null;
            $this->staffId = $appointment['staff_id'] ?? null;
            $this->clientId = $appointment['client_id'] ?? null;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $googleService, OutlookCalendarService $outlookService)
    {
        Log::info("SyncCalendarJob starting: Action '{$this->action}', Provider '{$this->calendarProvider}' for Appointment #{$this->appointmentId}");

        // Reconstruct appointment instance
        $appointment = new Appointment();
        $appointment->id = $this->appointmentId;
        $appointment->title = $this->title;
        $appointment->note = $this->note;
        $appointment->start_time = $this->startTime;
        $appointment->end_time = $this->endTime;
        $appointment->google_event_id = $this->googleEventId;
        $appointment->outlook_event_id = $this->outlookEventId;
        $appointment->calendar_provider = $this->calendarProvider;
        $appointment->staff_id = $this->staffId;
        $appointment->client_id = $this->clientId;

        // Perform synchronization
        if ($this->calendarProvider === 'google') {
            $result = $googleService->syncAppointment($appointment, $this->action);
            if ($result['success'] && $this->action !== 'delete') {
                $dbAppointment = Appointment::find($this->appointmentId);
                if ($dbAppointment) {
                    $dbAppointment->updateQuietly([
                        'google_event_id' => $result['event_id'],
                        'meeting_link' => $result['meeting_link'],
                        'calendar_provider' => 'google'
                    ]);
                }
            }
        } elseif ($this->calendarProvider === 'outlook') {
            $result = $outlookService->syncAppointment($appointment, $this->action);
            if ($result['success'] && $this->action !== 'delete') {
                $dbAppointment = Appointment::find($this->appointmentId);
                if ($dbAppointment) {
                    $dbAppointment->updateQuietly([
                        'outlook_event_id' => $result['event_id'],
                        'meeting_link' => $result['meeting_link'],
                        'calendar_provider' => 'outlook'
                    ]);
                }
            }
        } else {
            Log::warning("SyncCalendarJob: Unhandled provider '{$this->calendarProvider}'");
        }
    }
}
