<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AiService;
use App\Models\Activity;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function summarize(Request $request, Appointment $appointment, AiService $aiService)
    {
        $notes = $request->input('notes', '');
        
        $result = $aiService->summarizeMeeting($appointment, $notes);

        // Log this summary to CRM activities if the client belongs to a CRM B2B contact
        $contact = \App\Models\Contact::where('email', $appointment->client->email)->first();
        if ($contact) {
            Activity::create([
                'company_id' => $contact->company_id,
                'contact_id' => $contact->id,
                'user_id' => auth()->id(),
                'type' => 'meeting',
                'description' => "AI Meeting Summary for '{$appointment->title}': " . $result['summary']
            ]);
        }

        return response()->json($result);
    }
}
