<?php

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Services\ChatbotAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatbotAnalyticsController extends Controller
{
    protected ChatbotAnalyticsService $analyticsService;
    
    public function __construct(ChatbotAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }
    
    /**
     * Show analytics dashboard
     */
    public function index(Request $request): View
    {
        $chatbots = Chatbot::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        
        $selectedChatbot = null;
        if ($request->has('chatbot_id')) {
            $selectedChatbot = Chatbot::where('id', $request->chatbot_id)
                ->first(); // Temporal: remover filtro de user_id para debug
        } else if ($chatbots->isNotEmpty()) {
            $selectedChatbot = $chatbots->first();
        }
        
        $analytics = null;
        if ($selectedChatbot) {
            $days = $request->get('days', 30);
            $analytics = $this->analyticsService->getAnalytics($selectedChatbot, (int) $days);
        }
        
        return view('chatbot::analytics.dashboard', [
            'chatbots' => $chatbots,
            'selectedChatbot' => $selectedChatbot,
            'analytics' => $analytics,
            'days' => $request->get('days', 30)
        ]);
    }
    
    /**
     * Get analytics data as JSON
     */
    public function data(Request $request, string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $days = $request->get('days', 30);
        $analytics = $this->analyticsService->getAnalytics($chatbot, (int) $days);
        
        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
    
    /**
     * Export analytics report
     */
    public function export(Request $request, string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $days = $request->get('days', 30);
        $analytics = $this->analyticsService->getAnalytics($chatbot, (int) $days);
        
        // TODO: Implement PDF/CSV export
        
        return response()->json([
            'success' => true,
            'message' => 'Export functionality coming soon',
            'data' => $analytics
        ]);
    }
}
