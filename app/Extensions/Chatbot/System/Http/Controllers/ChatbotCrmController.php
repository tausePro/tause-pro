<?php

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotCustomer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotCrmController extends Controller
{
    /**
     * Export leads as JSON or CSV
     */
    public function exportLeads(Request $request, Chatbot $chatbot): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Verify ownership
        if ($chatbot->user_id !== Auth::id()) {
            abort(403);
        }

        $format = $request->get('format', 'json'); // json or csv

        $leads = ChatbotCustomer::query()
            ->where('chatbot_id', $chatbot->id)
            ->whereNotNull('email')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($format === 'csv') {
            return $this->exportAsCsv($leads, $chatbot->title);
        }

        return response()->json([
            'chatbot' => [
                'id' => $chatbot->id,
                'title' => $chatbot->title,
            ],
            'total_leads' => $leads->count(),
            'leads' => $leads->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'country_code' => $lead->country_code,
                    'ip_address' => $lead->ip_address,
                    'chatbot_channel' => $lead->chatbot_channel,
                    'gdpr_consent' => $lead->gdpr_consent,
                    'gdpr_consent_at' => $lead->gdpr_consent_at?->toDateTimeString(),
                    'crm_tags' => $lead->crm_tags,
                    'crm_status' => $lead->crm_status,
                    'created_at' => $lead->created_at->toDateTimeString(),
                ];
            }),
        ]);
    }

    /**
     * Export leads as CSV
     */
    private function exportAsCsv($leads, $chatbotTitle): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'leads_' . str_replace(' ', '_', $chatbotTitle) . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Country',
                'IP Address',
                'Channel',
                'GDPR Consent',
                'GDPR Consent Date',
                'CRM Tags',
                'CRM Status',
                'Created At',
            ]);

            // CSV Data
            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->id,
                    $lead->name,
                    $lead->email,
                    $lead->phone,
                    $lead->country_code,
                    $lead->ip_address,
                    $lead->chatbot_channel,
                    $lead->gdpr_consent ? 'Yes' : 'No',
                    $lead->gdpr_consent_at?->toDateTimeString(),
                    is_array($lead->crm_tags) ? implode(', ', $lead->crm_tags) : '',
                    $lead->crm_status,
                    $lead->created_at->toDateTimeString(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get leads statistics
     */
    public function getLeadsStats(Chatbot $chatbot): JsonResponse
    {
        // Verify ownership
        if ($chatbot->user_id !== Auth::id()) {
            abort(403);
        }

        $totalLeads = ChatbotCustomer::query()
            ->where('chatbot_id', $chatbot->id)
            ->whereNotNull('email')
            ->count();

        $leadsWithGdpr = ChatbotCustomer::query()
            ->where('chatbot_id', $chatbot->id)
            ->whereNotNull('email')
            ->where('gdpr_consent', true)
            ->count();

        $leadsToday = ChatbotCustomer::query()
            ->where('chatbot_id', $chatbot->id)
            ->whereNotNull('email')
            ->whereDate('created_at', today())
            ->count();

        $leadsByChannel = ChatbotCustomer::query()
            ->where('chatbot_id', $chatbot->id)
            ->whereNotNull('email')
            ->selectRaw('chatbot_channel, COUNT(*) as count')
            ->groupBy('chatbot_channel')
            ->get();

        return response()->json([
            'total_leads' => $totalLeads,
            'leads_with_gdpr_consent' => $leadsWithGdpr,
            'leads_today' => $leadsToday,
            'leads_by_channel' => $leadsByChannel,
        ]);
    }
}



