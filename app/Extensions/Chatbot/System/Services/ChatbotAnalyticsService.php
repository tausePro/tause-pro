<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChatbotAnalyticsService
{
    /**
     * Get comprehensive analytics for a chatbot
     */
    public function getAnalytics(Chatbot $chatbot, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        return [
            'overview' => $this->getOverviewMetrics($chatbot, $startDate),
            'conversations' => $this->getConversationMetrics($chatbot, $startDate),
            'messages' => $this->getMessageMetrics($chatbot, $startDate),
            'engagement' => $this->getEngagementMetrics($chatbot, $startDate),
            'channels' => $this->getChannelMetrics($chatbot, $startDate),
            'performance' => $this->getPerformanceMetrics($chatbot, $startDate),
            'trends' => $this->getTrendData($chatbot, $startDate, $days),
        ];
    }
    
    /**
     * Get overview metrics
     */
    protected function getOverviewMetrics(Chatbot $chatbot, Carbon $startDate): array
    {
        $conversations = ChatbotConversation::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $messages = ChatbotHistory::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $totalConversations = $conversations->count();
        $totalMessages = $messages->count();
        $avgMessagesPerConv = $totalConversations > 0 ? $totalMessages / $totalConversations : 0;
        
        // Calcular tasa de conversión (asumiendo que una conversación con >5 mensajes es exitosa)
        $successfulConversations = $conversations->filter(function($conv) use ($messages) {
            $convMessages = $messages->where('conversation_id', $conv->id)->count();
            return $convMessages >= 5;
        })->count();
        
        $conversionRate = $totalConversations > 0 
            ? ($successfulConversations / $totalConversations) * 100 
            : 0;
        
        return [
            'total_conversations' => $totalConversations,
            'total_messages' => $totalMessages,
            'avg_messages_per_conversation' => round($avgMessagesPerConv, 2),
            'successful_conversations' => $successfulConversations,
            'conversion_rate' => round($conversionRate, 2),
            'unique_users' => $conversations->unique('chatbot_customer_id')->count(),
        ];
    }
    
    /**
     * Get conversation metrics
     */
    protected function getConversationMetrics(Chatbot $chatbot, Carbon $startDate): array
    {
        $conversations = ChatbotConversation::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $avgDuration = 0;
        $completedConversations = 0;
        
        foreach ($conversations as $conv) {
            if ($conv->updated_at && $conv->created_at) {
                $duration = $conv->created_at->diffInMinutes($conv->updated_at);
                $avgDuration += $duration;
                if ($duration > 1) {
                    $completedConversations++;
                }
            }
        }
        
        $avgDuration = $conversations->count() > 0 
            ? $avgDuration / $conversations->count() 
            : 0;
        
        return [
            'total' => $conversations->count(),
            'completed' => $completedConversations,
            'avg_duration_minutes' => round($avgDuration, 2),
            'new_conversations_today' => ChatbotConversation::where('chatbot_id', $chatbot->id)
                ->whereDate('created_at', Carbon::today())
                ->count(),
        ];
    }
    
    /**
     * Get message metrics
     */
    protected function getMessageMetrics(Chatbot $chatbot, Carbon $startDate): array
    {
        $messages = ChatbotHistory::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $botMessages = $messages->where('role', 'assistant')->count();
        $userMessages = $messages->where('role', 'user')->count();
        
        return [
            'total' => $messages->count(),
            'bot_messages' => $botMessages,
            'user_messages' => $userMessages,
            'avg_response_time_seconds' => $this->calculateAvgResponseTime($messages),
            'messages_today' => ChatbotHistory::where('chatbot_id', $chatbot->id)
                ->whereDate('created_at', Carbon::today())
                ->count(),
        ];
    }
    
    /**
     * Get engagement metrics
     */
    protected function getEngagementMetrics(Chatbot $chatbot, Carbon $startDate): array
    {
        $conversations = ChatbotConversation::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        // Calcular bounce rate (conversaciones con solo 1-2 mensajes)
        $bounced = $conversations->filter(function($conv) {
            $messageCount = ChatbotHistory::where('conversation_id', $conv->id)->count();
            return $messageCount <= 2;
        })->count();
        
        $bounceRate = $conversations->count() > 0 
            ? ($bounced / $conversations->count()) * 100 
            : 0;
        
        // Calcular engagement score
        $engagementScore = $this->calculateEngagementScore($chatbot, $startDate);
        
        return [
            'bounce_rate' => round($bounceRate, 2),
            'return_users' => $this->getReturnUsers($chatbot, $startDate),
            'engagement_score' => round($engagementScore, 2),
            'avg_messages_per_user' => $this->getAvgMessagesPerUser($chatbot, $startDate),
        ];
    }
    
    /**
     * Get channel metrics
     */
    protected function getChannelMetrics(Chatbot $chatbot, Carbon $startDate): array
    {
        $conversations = ChatbotConversation::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $channelStats = [];
        $channels = ['web', 'whatsapp', 'telegram', 'messenger'];
        
        foreach ($channels as $channel) {
            $channelConvs = $conversations->where('channel', $channel);
            $channelStats[$channel] = [
                'conversations' => $channelConvs->count(),
                'percentage' => $conversations->count() > 0 
                    ? round(($channelConvs->count() / $conversations->count()) * 100, 2) 
                    : 0
            ];
        }
        
        return $channelStats;
    }
    
    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(Chatbot $chatbot, Carbon $startDate): array
    {
        return [
            'human_agent_transfers' => $this->getHumanAgentTransfers($chatbot, $startDate),
            'resolved_by_bot' => $this->getResolvedByBot($chatbot, $startDate),
            'customer_satisfaction' => $this->getCustomerSatisfaction($chatbot, $startDate),
            'peak_hours' => $this->getPeakHours($chatbot, $startDate),
        ];
    }
    
    /**
     * Get trend data for charts
     */
    protected function getTrendData(Chatbot $chatbot, Carbon $startDate, int $days): array
    {
        $daily = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($days - $i - 1);
            
            $daily[] = [
                'date' => $date->format('Y-m-d'),
                'conversations' => ChatbotConversation::where('chatbot_id', $chatbot->id)
                    ->whereDate('created_at', $date)
                    ->count(),
                'messages' => ChatbotHistory::where('chatbot_id', $chatbot->id)
                    ->whereDate('created_at', $date)
                    ->count(),
            ];
        }
        
        return $daily;
    }
    
    /**
     * Helper methods
     */
    protected function calculateAvgResponseTime($messages): float
    {
        // Simplified - calculate average time between user message and bot response
        $responseTimes = [];
        $userMessages = $messages->where('role', 'user')->sortBy('created_at');
        
        foreach ($userMessages as $userMsg) {
            $nextBotMsg = $messages
                ->where('role', 'assistant')
                ->where('conversation_id', $userMsg->conversation_id)
                ->where('created_at', '>', $userMsg->created_at)
                ->sortBy('created_at')
                ->first();
            
            if ($nextBotMsg) {
                $diff = $userMsg->created_at->diffInSeconds($nextBotMsg->created_at);
                if ($diff > 0 && $diff < 300) { // Max 5 minutes
                    $responseTimes[] = $diff;
                }
            }
        }
        
        return count($responseTimes) > 0 
            ? array_sum($responseTimes) / count($responseTimes) 
            : 0;
    }
    
    protected function calculateEngagementScore(Chatbot $chatbot, Carbon $startDate): float
    {
        // Score basado en múltiples factores
        $overview = $this->getOverviewMetrics($chatbot, $startDate);
        $conversation = $this->getConversationMetrics($chatbot, $startDate);
        
        $score = 0;
        $score += min(($overview['avg_messages_per_conversation'] / 10) * 30, 30); // Max 30 points
        $score += min(($overview['conversion_rate'] / 100) * 40, 40); // Max 40 points
        $score += min(($conversation['completed'] / max($conversation['total'], 1)) * 30, 30); // Max 30 points
        
        return min($score, 100);
    }
    
    protected function getReturnUsers(Chatbot $chatbot, Carbon $startDate): int
    {
        return ChatbotConversation::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->select('chatbot_customer_id')
            ->groupBy('chatbot_customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }
    
    protected function getAvgMessagesPerUser(Chatbot $chatbot, Carbon $startDate): float
    {
        $conversations = ChatbotConversation::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $uniqueUsers = $conversations->unique('chatbot_customer_id')->count();
        $totalMessages = ChatbotHistory::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->where('role', 'user')
            ->count();
        
        return $uniqueUsers > 0 ? $totalMessages / $uniqueUsers : 0;
    }
    
    protected function getHumanAgentTransfers(Chatbot $chatbot, Carbon $startDate): int
    {
        return ChatbotHistory::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->where('message', 'like', '%#humanagent%')
            ->count();
    }
    
    protected function getResolvedByBot(Chatbot $chatbot, Carbon $startDate): int
    {
        $total = ChatbotConversation::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $humanTransfers = $this->getHumanAgentTransfers($chatbot, $startDate);
        
        return max($total - $humanTransfers, 0);
    }
    
    protected function getCustomerSatisfaction(Chatbot $chatbot, Carbon $startDate): ?float
    {
        // TODO: Implementar sistema de ratings
        return null;
    }
    
    protected function getPeakHours(Chatbot $chatbot, Carbon $startDate): array
    {
        $messages = ChatbotHistory::where('chatbot_id', $chatbot->id)
            ->where('created_at', '>=', $startDate)
            ->get();
        
        $hourly = [];
        for ($i = 0; $i < 24; $i++) {
            $hourly[$i] = 0;
        }
        
        foreach ($messages as $message) {
            $hour = (int) $message->created_at->format('H');
            $hourly[$hour]++;
        }
        
        arsort($hourly);
        
        return array_slice(array_keys($hourly), 0, 3, true);
    }
}
