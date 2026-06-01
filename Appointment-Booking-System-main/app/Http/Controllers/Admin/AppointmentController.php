<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\AvailabilityService;
use App\Jobs\SyncCalendarJob;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['client', 'staff']);
        $user = auth()->user();

        if ($user->role === 'client') {
            $query->where('client_id', $user->id);
        } elseif ($user->role === 'staff') {
            $query->where('staff_id', $user->id);
        } else {
            if ($request->has('client_id') && !empty($request->client_id)) {
                $query->where('client_id', $request->client_id);
            }
            if ($request->has('staff_id') && !empty($request->staff_id)) {
                $query->where('staff_id', $request->staff_id);
            }
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('staff', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('all') && $request->all === 'true') {
            return response()->json($query->orderBy('start_time', 'asc')->get());
        }

        return response()->json($query->orderBy('start_time', 'asc')->paginate(10));
    }

    public function store(Request $request, AvailabilityService $availabilityService, \App\Services\SequenceService $sequenceService)
    {
        $user = auth()->user();
        if ($user->role === 'client') {
            $request->merge([
                'client_id' => $user->id,
                'status' => 'scheduled'
            ]);
        }

        $fields = $request->validate([
            'client_id' => ['required', 'exists:users,id'],
            'staff_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'status' => ['required', Rule::in(['scheduled', 'confirmed', 'completed', 'cancelled'])],
            'note' => ['nullable', 'string'],
            'calendar_provider' => ['nullable', 'string', Rule::in(['google', 'outlook', 'none'])],
        ]);

        // Default calendar provider if not provided
        if (!isset($fields['calendar_provider'])) {
            $fields['calendar_provider'] = 'google';
        }

        $provider = \App\Models\User::find($fields['staff_id']);
        $check = $availabilityService->checkAvailability($provider, $fields['start_time'], $fields['end_time']);

        if (!$check['available']) {
            return response()->json([
                'message' => $check['reason']
            ], 422);
        }

        $appointment = Appointment::create($fields);
        $appointment->load(['client', 'staff']);

        if (in_array($appointment->calendar_provider, ['google', 'outlook'])) {
            SyncCalendarJob::dispatch($appointment, 'create');
        }

        // Trigger sequence notifications & reminders
        $sequenceService->triggerAppointmentSequences($appointment);

        \Log::info("SMS/Email Confirmation Dispatched: Appointment #{$appointment->id} ('{$appointment->title}') scheduled for client {$appointment->client->name} with provider {$appointment->staff->name} on {$appointment->start_time}.");

        return response()->json($appointment, 201);
    }

    public function show(Appointment $appointment)
    {
        $user = auth()->user();
        if ($user->role === 'client' && $appointment->client_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($user->role === 'staff' && $appointment->staff_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($appointment->load(['client', 'staff']));
    }

    public function update(Request $request, Appointment $appointment, AvailabilityService $availabilityService)
    {
        $user = auth()->user();
        if ($user->role === 'client') {
            if ($appointment->client_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
            $request->merge([
                'client_id' => $user->id,
            ]);
            if ($request->input('status') !== 'cancelled') {
                $request->merge(['status' => $appointment->status]);
            }
        } elseif ($user->role === 'staff') {
            if ($appointment->staff_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        $fields = $request->validate([
            'client_id' => ['required', 'exists:users,id'],
            'staff_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'status' => ['required', Rule::in(['scheduled', 'confirmed', 'completed', 'cancelled'])],
            'note' => ['nullable', 'string'],
            'calendar_provider' => ['nullable', 'string', Rule::in(['google', 'outlook', 'none'])],
        ]);

        $provider = \App\Models\User::find($fields['staff_id']);
        $check = $availabilityService->checkAvailability($provider, $fields['start_time'], $fields['end_time'], $appointment->id);

        if (!$check['available']) {
            return response()->json([
                'message' => $check['reason']
            ], 422);
        }

        $oldProvider = $appointment->calendar_provider;
        $oldGoogleId = $appointment->google_event_id;
        $oldOutlookId = $appointment->outlook_event_id;

        $appointment->update($fields);
        $appointment->load(['client', 'staff']);

        if ($oldProvider !== $appointment->calendar_provider) {
            // Delete old event
            if ($oldProvider === 'google' && $oldGoogleId) {
                SyncCalendarJob::dispatch([
                    'id' => $appointment->id,
                    'google_event_id' => $oldGoogleId,
                    'calendar_provider' => 'google'
                ], 'delete');
            } elseif ($oldProvider === 'outlook' && $oldOutlookId) {
                SyncCalendarJob::dispatch([
                    'id' => $appointment->id,
                    'outlook_event_id' => $oldOutlookId,
                    'calendar_provider' => 'outlook'
                ], 'delete');
            }
            // Create new event
            if (in_array($appointment->calendar_provider, ['google', 'outlook'])) {
                SyncCalendarJob::dispatch($appointment, 'create');
            }
        } else {
            // Same provider, just update
            if (in_array($appointment->calendar_provider, ['google', 'outlook'])) {
                SyncCalendarJob::dispatch($appointment, 'update');
            }
        }

        \Log::info("SMS/Email Notification Dispatched: Appointment #{$appointment->id} ('{$appointment->title}') updated to status '{$appointment->status}' for client {$appointment->client->name}.");

        return response()->json($appointment);
    }

    public function destroy(Appointment $appointment)
    {
        $user = auth()->user();
        if ($user->role === 'client' && $appointment->client_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($user->role === 'staff' && $appointment->staff_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (in_array($appointment->calendar_provider, ['google', 'outlook'])) {
            SyncCalendarJob::dispatch([
                'id' => $appointment->id,
                'google_event_id' => $appointment->google_event_id,
                'outlook_event_id' => $appointment->outlook_event_id,
                'calendar_provider' => $appointment->calendar_provider,
            ], 'delete');
        }

        $appointment->delete();
        return response()->json(['message' => 'Appointment deleted successfully.']);
    }
}
