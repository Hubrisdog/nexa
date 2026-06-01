<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected $isEnabled;
    protected $accessToken;
    protected $calendarId;

    public function __construct()
    {
        // Checked if credentials are set in .env
        $this->isEnabled = !empty(env('GOOGLE_CALENDAR_CLIENT_ID')) && !empty(env('GOOGLE_CALENDAR_CLIENT_SECRET'));
        $this->accessToken = env('GOOGLE_CALENDAR_ACCESS_TOKEN');
        $this->calendarId = env('GOOGLE_CALENDAR_ID', 'primary');
    }

    /**
     * Synchronizes appointment modifications to Google Calendar.
     * Generates a Google Meet link dynamically.
     */
    public function syncAppointment(Appointment $appointment, $action = 'create')
    {
        if (!$this->isEnabled) {
            Log::info("Google Calendar Sync [MOCK]: Action '{$action}' successfully executed for Appointment #{$appointment->id} ('{$appointment->title}'). Scheduled time: {$appointment->start_time}. Mock Google Meet link created: https://meet.google.com/nexa-{$appointment->id}-meet");
            return [
                'success' => true,
                'mock' => true,
                'event_id' => 'mock-google-event-' . $appointment->id,
                'meeting_link' => "https://meet.google.com/nexa-{$appointment->id}-meet"
            ];
        }

        try {
            if ($action === 'delete') {
                if (!$appointment->google_event_id) {
                    return ['success' => true];
                }
                $response = Http::withToken($this->accessToken)
                    ->delete("https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events/{$appointment->google_event_id}");

                return ['success' => $response->successful()];
            }

            $payload = [
                'summary' => $appointment->title,
                'description' => $appointment->note ?? 'Scheduled via Nexa Console.',
                'start' => [
                    'dateTime' => date(DATE_ISO8601, strtotime($appointment->start_time)),
                    'timeZone' => config('app.timezone', 'UTC'),
                ],
                'end' => [
                    'dateTime' => date(DATE_ISO8601, strtotime($appointment->end_time)),
                    'timeZone' => config('app.timezone', 'UTC'),
                ],
            ];

            // Request Google Meet conference option on creations
            if ($action === 'create') {
                $payload['conferenceData'] = [
                    'createRequest' => [
                        'requestId' => 'nexa-meet-' . $appointment->id . '-' . time(),
                        'conferenceSolutionKey' => [
                            'type' => 'eventHangout'
                        ]
                    ]
                ];
            }

            if ($action === 'update' && $appointment->google_event_id) {
                $url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events/{$appointment->google_event_id}";
                $response = Http::withToken($this->accessToken)->put($url, $payload);
            } else {
                $url = "https://www.googleapis.com/calendar/v3/calendars/{$this->calendarId}/events?conferenceDataVersion=1";
                $response = Http::withToken($this->accessToken)->post($url, $payload);
            }

            if ($response->successful()) {
                $data = $response->json();
                $meetLink = null;

                if (isset($data['conferenceData']['entryPoints'])) {
                    foreach ($data['conferenceData']['entryPoints'] as $entry) {
                        if ($entry['entryPointType'] === 'video') {
                            $meetLink = $entry['uri'];
                            break;
                        }
                    }
                }

                return [
                    'success' => true,
                    'event_id' => $data['id'] ?? null,
                    'meeting_link' => $meetLink ?? $data['hangoutLink'] ?? null
                ];
            }

            Log::error("Google Calendar Sync failed with body: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Google Calendar Service Exception: " . $e->getMessage());
        }

        return ['success' => false];
    }
}
