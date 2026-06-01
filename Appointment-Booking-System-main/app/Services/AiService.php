<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected $geminiKey;
    protected $isEnabled;

    public function __construct()
    {
        $this->geminiKey = env('GEMINI_API_KEY');
        $this->isEnabled = !empty($this->geminiKey);
    }

    /**
     * Scores a CRM deal based on its details, company size, and activities.
     */
    public function scoreLead(Deal $deal): int
    {
        if (!$this->isEnabled) {
            // Algorithmic fallback score
            $score = 50; // base score

            if ($deal->value > 50000) $score += 15;
            elseif ($deal->value > 10000) $score += 10;

            if ($deal->company) {
                if ($deal->company->employee_count > 100) $score += 15;
                elseif ($deal->company->employee_count > 20) $score += 10;
                
                if ($deal->company->industry) $score += 5;
                if ($deal->company->website) $score += 5;
            }

            if ($deal->contact) {
                if ($deal->contact->linkedin_url) $score += 5;
                if ($deal->contact->email) $score += 5;
            }

            return min(100, $score);
        }

        try {
            $companyName = $deal->company->name ?? 'N/A';
            $industry = $deal->company->industry ?? 'N/A';
            $employeeCount = $deal->company->employee_count ?? 'N/A';
            $value = $deal->value;

            $prompt = "Evaluate this B2B sales deal opportunity and return ONLY an integer between 0 and 100 representing the lead qualification score.
            Deal Details:
            - Title: {$deal->title}
            - Value: ${$value}
            - Company: {$companyName}
            - Industry: {$industry}
            - Employees: {$employeeCount}";

            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiKey}";
            
            $response = Http::post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $score = intval(trim($text));
                if ($score >= 0 && $score <= 100) {
                    return $score;
                }
            }

            Log::error("Gemini API Error: " . $response->body());
        } catch (\Exception $e) {
            Log::error("AiService Lead Score Exception: " . $e->getMessage());
        }

        return 50; // default fallback
    }

    /**
     * Generates a meeting summary & action items from transcripts or notes.
     */
    public function summarizeMeeting(Appointment $appointment, $notes = ''): array
    {
        $inputNotes = $notes ?: $appointment->note ?: 'No notes provided.';

        if (!$this->isEnabled) {
            return [
                'summary' => "The meeting with client {$appointment->client->name} and provider {$appointment->staff->name} regarding \"{$appointment->title}\" was successfully completed.",
                'highlights' => [
                    "Discussed scheduled agenda and requirements.",
                    "Clarified project alignment and timelines."
                ],
                'action_items' => [
                    "Follow up on proposals.",
                    "Schedule next sync session if requested."
                ],
                'mock' => true
            ];
        }

        try {
            $prompt = "Summarize the following meeting notes/transcript. Provide a JSON response containing:
            1. 'summary': A 2-sentence overview.
            2. 'highlights': An array of key discussion points.
            3. 'action_items': An array of next steps.
            
            Notes:
            {$inputNotes}";

            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiKey}";
            $response = Http::post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            if ($response->successful()) {
                $text = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $cleaned = preg_replace('/```json\s*|```/', '', $text);
                $data = json_decode(trim($cleaned), true);
                if (isset($data['summary'])) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            Log::error("AiService Summarization Exception: " . $e->getMessage());
        }

        return [
            'summary' => "The meeting regarding \"{$appointment->title}\" was successfully completed.",
            'highlights' => ["Standard checkup conducted."],
            'action_items' => ["No outstanding tasks logged."],
            'mock' => true
        ];
    }
}
