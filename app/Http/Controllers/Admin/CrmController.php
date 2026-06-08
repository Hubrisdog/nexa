<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmController extends Controller
{
    public function getPipeline()
    {
        $deals = Deal::with(['company', 'contact'])->get();
        return response()->json($deals);
    }

    public function updateDealStage(Request $request, Deal $deal, \App\Services\WebhookService $webhookService)
    {
        $fields = $request->validate([
            'stage' => ['required', Rule::in(['cold', 'contacted', 'interested', 'booked', 'closed_won', 'closed_lost'])]
        ]);

        $oldStage = $deal->stage;
        $deal->update([
            'stage' => $fields['stage']
        ]);

        // Automatically log an activity when a deal stage changes
        Activity::create([
            'company_id' => $deal->company_id,
            'contact_id' => $deal->contact_id,
            'user_id' => auth()->id(),
            'type' => 'note',
            'description' => "Deal '{$deal->title}' stage updated to: " . ucfirst($fields['stage'])
        ]);

        // Audit Log
        \App\Models\AuditLog::log(
            'deal_stage_updated',
            $deal,
            ['stage' => $oldStage],
            ['stage' => $fields['stage']],
            "Moved deal '{$deal->title}' from " . ucfirst($oldStage) . " to " . ucfirst($fields['stage'])
        );

        // Dispatch Webhook Event
        $webhookService->dispatch('deal.updated', [
            'event' => 'deal.updated',
            'deal_id' => $deal->id,
            'title' => $deal->title,
            'value' => $deal->value,
            'stage' => $deal->stage,
            'old_stage' => $oldStage,
            'score' => $deal->score,
            'tenant_id' => $deal->tenant_id,
            'timestamp' => now()->toIso8601String()
        ], $deal->tenant_id);

        return response()->json($deal->load(['company', 'contact']));
    }

    public function storeDeal(Request $request, \App\Services\AiService $aiService)
    {
        $user = auth()->user();
        $fields = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('tenant_id', $user->tenant_id)
            ],
            'contact_id' => [
                'nullable',
                Rule::exists('contacts', 'id')->where('tenant_id', $user->tenant_id)
            ],
            'value' => ['required', 'numeric', 'min:0'],
            'stage' => ['required', Rule::in(['cold', 'contacted', 'interested', 'booked', 'closed_won', 'closed_lost'])],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $deal = Deal::create($fields);

        if (!isset($fields['score']) || is_null($fields['score']) || $fields['score'] === '') {
            $deal->load(['company', 'contact']);
            $score = $aiService->scoreLead($deal);
            $deal->update(['score' => $score]);
        }

        // Log deal creation activity
        Activity::create([
            'company_id' => $deal->company_id,
            'contact_id' => $deal->contact_id,
            'user_id' => auth()->id(),
            'type' => 'note',
            'description' => "New deal created: '{$deal->title}' with value $" . number_format($deal->value, 2)
        ]);

        // Audit Log
        \App\Models\AuditLog::log(
            'deal_created',
            $deal,
            null,
            $deal->only(['title', 'value', 'stage', 'score']),
            "Created deal '{$deal->title}' valued at $" . number_format($deal->value, 2)
        );

        return response()->json($deal->load(['company', 'contact']), 201);
    }

    public function destroyDeal(Deal $deal)
    {
        // Audit Log
        \App\Models\AuditLog::log(
            'deal_deleted',
            $deal,
            $deal->only(['title', 'value', 'stage']),
            null,
            "Deleted deal '{$deal->title}'"
        );

        $deal->delete();
        return response()->json(['message' => 'Deal deleted successfully.']);
    }

    public function getCompanies()
    {
        return response()->json(Company::with('contacts')->get());
    }

    public function storeCompany(Request $request)
    {
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'revenue' => ['nullable', 'string', 'max:255'],
            'employee_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $company = Company::create($fields);
        return response()->json($company, 201);
    }

    public function getContacts()
    {
        return response()->json(Contact::with('company')->get());
    }

    public function storeContact(Request $request)
    {
        $user = auth()->user();
        $fields = $request->validate([
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('tenant_id', $user->tenant_id)
            ],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'linkedin_url' => ['nullable', 'string', 'max:255'],
        ]);

        $contact = Contact::create($fields);
        return response()->json($contact->load('company'), 201);
    }

    public function getActivities(Request $request)
    {
        $query = Activity::with(['company', 'contact', 'user']);

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->has('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        $activities = $query->orderBy('created_at', 'desc')->get();
        return response()->json($activities);
    }

    public function storeActivity(Request $request)
    {
        $user = auth()->user();
        $fields = $request->validate([
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('tenant_id', $user->tenant_id)
            ],
            'contact_id' => [
                'nullable',
                Rule::exists('contacts', 'id')->where('tenant_id', $user->tenant_id)
            ],
            'type' => ['required', Rule::in(['call', 'email', 'note', 'meeting'])],
            'description' => ['required', 'string'],
        ]);

        $fields['user_id'] = auth()->id();

        $activity = Activity::create($fields);
        return response()->json($activity->load(['company', 'contact', 'user']), 201);
    }
}
