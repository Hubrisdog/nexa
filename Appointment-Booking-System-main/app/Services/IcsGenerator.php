<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;

class IcsGenerator
{
    /**
     * Generates a standard RFC 5545 iCalendar (.ics) string for an appointment.
     */
    public function generate(Appointment $appointment): string
    {
        $dtStart = Carbon::parse($appointment->start_time)->setTimezone('UTC')->format('Ymd\THis\Z');
        $dtEnd = Carbon::parse($appointment->end_time)->setTimezone('UTC')->format('Ymd\THis\Z');
        $dtStamp = Carbon::now()->setTimezone('UTC')->format('Ymd\THis\Z');
        
        $uid = 'apt-' . $appointment->id . '-' . uniqid() . '@nexa.com';
        $summary = $this->escapeString($appointment->title);
        $description = $this->escapeString($appointment->note ?? 'Nexa Discovery Sync Meeting');
        $location = $this->escapeString($appointment->meeting_link ?? 'Google Meet / Online');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Nexa SaaS//Nonsgml//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtStamp,
            'DTSTART:' . $dtStart,
            'DTEND:' . $dtEnd,
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $description,
            'LOCATION:' . $location,
            'END:VEVENT',
            'END:VCALENDAR',
            ''
        ]);
    }

    /**
     * Escapes special characters for iCalendar format.
     */
    protected function escapeString(?string $string): string
    {
        if (empty($string)) {
            return '';
        }
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(';', '\\;', $string);
        $string = str_replace(',', '\\,', $string);
        $string = str_replace("\n", '\\n', $string);
        $string = str_replace("\r", '', $string);
        return $string;
    }
}
