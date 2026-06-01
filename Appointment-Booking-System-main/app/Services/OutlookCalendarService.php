<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OutlookCalendarService
{
    protected $isEnabled;
    protected $accessToken;
    protected $calendarId;

    public function __construct()
    {
        // Checked if credentials are set in .env
        $this->isEnabled = !empty(env('OUTLOOK_CALENDAR_CLIENT_ID')) && !empty(env('OUTLOOK_CALENDAR_CLIENT_SECRET'));
        $this->accessToken = env('OUTLOOK_CALENDAR_ACCESS_TOKEN');
        $this->calendarId = env('OUTLOOK_CALENDAR_ID', 'me/calendar');
    }

    /**
     * Synchronizes appointment modifications to Outlook Calendar.
     * Generates a Microsoft Teams link dynamically.
     */
    public function syncAppointment(Appointment $appointment, $action = 'create')
    {
        if (!$this->isEnabled) {
            Log::info("Outlook Calendar Sync [MOCK]: Action '{$action}' successfully executed for Appointment #{$appointment->id} ('{$appointment->title}'). Scheduled time: {$appointment->start_time}. Mock Microsoft Teams link created: https://teams.microsoft.com/l/meetup-join/nexa-{$appointment->id}-teams");
            return [
                'success' => true,
                'mock' => true,
                'event_id' => 'mock-outlook-event-' . $appointment->id,
                'meeting_link' => "https://teams.microsoft.com/l/meetup-join/nexa-{$appointment->id}-teams"
            ];
        }

        try {
            if ($action === 'delete') {
                if (!$appointment->outlook_event_id) {
                    return ['success' => true];
                }
                $response = Http::withToken($this->accessToken)
                    ->delete("https://graph.microsoft.com/v1.0/{$this->calendarId}/events/{$appointment->outlook_event_id}");

                return ['success' => $response->successful()];
            }

            $payload = [
                'subject' => $appointment->title,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $appointment->note ?? 'Scheduled via Nexa Console.',
                ],
                'start' => [
                    'dateTime' => date('Y-m-d\TH:i:s', strtotime($appointment->start_time)),
                    'timeZone' => config('app.timezone', 'UTC'),
                ],
                'end' => [
                    'dateTime' => date('Y-m-d\TH:i:s', strtotime($appointment->end_time)),
                    'timeZone' => config('app.timezone', 'UTC'),
                ],
            ];

            // Request Microsoft Teams conference option on creations
            if ($action === 'create') {
                $payload['isOnlineMeeting'] = true;
                $payload['onlineMeetingProvider'] = 'teamsForBusiness';
            }

            if ($action === 'update' && $appointment->outlook_event_id) {
                $url = "https://graph.microsoft.com/v1.0/{$this->calendarId}/events/{$appointment->outlook_event_id}";
                $response = Http::withToken($this->accessToken)->patch($url, $payload);
            } else {
                $url = "https://graph.microsoft.com/v1.0/{$this->calendarId}/events";
                $response = Http::withToken($this->accessToken)->post($url, $payload);
            }

            if ($response->successful()) {
                $data = $response->json();
                $teamsLink = $data['onlineMeeting']['joinUrl'] ?? null;

                return [
                    'success' => true,
                    'event_id' => $data['id'] ?? null,
                    'meeting_link' => $teamsLink
                ];
            }

            Log::error("Outlook Calendar Sync failed with body: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Outlook Calendar Service Exception: " . $e->getMessage());
        }

        return ['success' => false];
    }
}
