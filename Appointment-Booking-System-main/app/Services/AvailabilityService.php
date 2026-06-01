<?php

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AvailabilityService
{
    /**
     * Checks if a provider (staff) is available for a requested slot.
     *
     * @param User $provider
     * @param string|\DateTime $startTime UTC / System Timezone
     * @param string|\DateTime $endTime UTC / System Timezone
     * @param int|null $excludeAppointmentId Exclude a specific appointment from conflict checks
     * @return array ['available' => bool, 'reason' => string|null]
     */
    public function checkAvailability(User $provider, $startTime, $endTime, $excludeAppointmentId = null): array
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        // Fetch availability settings
        $availability = $provider->availability;
        if (!$availability) {
            // If no settings exist, default to a standard 9-5 schedule.
            $availability = $provider->availability()->create([
                'working_hours' => [
                    'monday' => ['start' => '09:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                    'thursday' => ['start' => '09:00', 'end' => '17:00'],
                    'friday' => ['start' => '09:00', 'end' => '17:00'],
                ],
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'holidays' => [],
                'buffer_time' => 15,
                'timezone' => 'UTC',
            ]);
        }

        $timezone = $availability->timezone ?? 'UTC';

        // Convert times to provider timezone for schedule checks
        $localStart = $start->copy()->setTimezone($timezone);
        $localEnd = $end->copy()->setTimezone($timezone);

        $dayOfWeek = strtolower($localStart->englishDayOfWeek);
        $dateStr = $localStart->toDateString(); // Y-m-d in local timezone

        // 1. Check Holidays
        $holidays = $availability->holidays ?? [];
        if (in_array($dateStr, $holidays)) {
            return [
                'available' => false,
                'reason' => "The selected date ({$dateStr}) is a holiday for this provider."
            ];
        }

        // 2. Check Working Days & Hours
        $workingHours = $availability->working_hours ?? [];
        if (!isset($workingHours[$dayOfWeek])) {
            return [
                'available' => false,
                'reason' => "The provider does not work on " . ucfirst($dayOfWeek) . "."
            ];
        }

        $daySchedule = $workingHours[$dayOfWeek];
        $workStart = Carbon::parse($localStart->toDateString() . ' ' . $daySchedule['start'], $timezone);
        $workEnd = Carbon::parse($localStart->toDateString() . ' ' . $daySchedule['end'], $timezone);

        if ($localStart->lt($workStart) || $localEnd->gt($workEnd)) {
            return [
                'available' => false,
                'reason' => "Requested time is outside working hours (" . $daySchedule['start'] . " - " . $daySchedule['end'] . " {$timezone})."
            ];
        }

        // 3. Check Breaks
        $breaks = $availability->breaks ?? [];
        foreach ($breaks as $break) {
            $breakStart = Carbon::parse($localStart->toDateString() . ' ' . $break['start'], $timezone);
            $breakEnd = Carbon::parse($localStart->toDateString() . ' ' . $break['end'], $timezone);

            // Overlap check
            if ($localStart->lt($breakEnd) && $localEnd->gt($breakStart)) {
                return [
                    'available' => false,
                    'reason' => "Requested slot conflicts with the provider's scheduled break (" . $break['start'] . " - " . $break['end'] . ")."
                ];
            }
        }

        // 4. Check existing appointments and buffer times
        $bufferMinutes = $availability->buffer_time ?? 0;

        $existingAppointments = Appointment::where('staff_id', $provider->id)
            ->where('status', '!=', 'cancelled')
            ->when($excludeAppointmentId, function ($q) use ($excludeAppointmentId) {
                $q->where('id', '!=', $excludeAppointmentId);
            })
            ->get();

        foreach ($existingAppointments as $apt) {
            // Apply buffer after the appointment end
            $aptStart = Carbon::parse($apt->start_time);
            $aptEnd = Carbon::parse($apt->end_time)->addMinutes($bufferMinutes);

            // Overlap check including the buffer
            if ($start->lt($aptEnd) && $end->gt($aptStart)) {
                return [
                    'available' => false,
                    'reason' => "Conflict with an existing booking (including a {$bufferMinutes} min buffer)."
                ];
            }
        }

        return [
            'available' => true,
            'reason' => null
        ];
    }
}
