<?php

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotAnalytics extends Model
{
    protected $table = 'ext_chatbot_analytics';

    protected $fillable = [
        'chatbot_id',
        'article_id',
        'query',
        'channel',
        'response_time',
        'user_satisfaction',
        'conversion',
        'session_id',
        'metadata',
    ];

    protected $casts = [
        'response_time' => 'integer',
        'user_satisfaction' => 'integer',
        'conversion' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the chatbot that this analytics record belongs to.
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }

    /**
     * Get the knowledge base article that was accessed.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(ChatbotKnowledgeBaseArticle::class, 'article_id');
    }

    /**
     * Get formatted response time.
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if (!$this->response_time) {
            return 'N/A';
        }
        
        if ($this->response_time < 1000) {
            return $this->response_time . 'ms';
        }
        
        return round($this->response_time / 1000, 2) . 's';
    }

    /**
     * Get satisfaction rating as stars.
     */
    public function getSatisfactionStarsAttribute(): string
    {
        if (!$this->user_satisfaction) {
            return 'No rating';
        }
        
        return str_repeat('★', $this->user_satisfaction) . 
               str_repeat('☆', 5 - $this->user_satisfaction);
    }

    /**
     * Get formatted channel name.
     */
    public function getFormattedChannelAttribute(): string
    {
        return match($this->channel) {
            'web' => 'Web Chat',
            'whatsapp' => 'WhatsApp',
            'messenger' => 'Facebook Messenger',
            'telegram' => 'Telegram',
            'voice' => 'Voice Chat',
            default => ucfirst($this->channel ?? 'Unknown')
        };
    }

    /**
     * Check if response was fast (under 2 seconds).
     */
    public function isFastResponse(): bool
    {
        return $this->response_time && $this->response_time < 2000;
    }

    /**
     * Check if user was satisfied (rating 4 or 5).
     */
    public function isUserSatisfied(): bool
    {
        return $this->user_satisfaction && $this->user_satisfaction >= 4;
    }

    /**
     * Scope to filter by chatbot.
     */
    public function scopeForChatbot($query, $chatbotId)
    {
        return $query->where('chatbot_id', $chatbotId);
    }

    /**
     * Scope to filter by channel.
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get conversions only.
     */
    public function scopeConversions($query)
    {
        return $query->where('conversion', true);
    }

    /**
     * Scope to get satisfied users only.
     */
    public function scopeSatisfied($query)
    {
        return $query->where('user_satisfaction', '>=', 4);
    }

    /**
     * Scope to get fast responses only.
     */
    public function scopeFastResponses($query)
    {
        return $query->where('response_time', '<', 2000);
    }

    /**
     * Scope to search by query.
     */
    public function scopeSearchQuery($query, $term)
    {
        return $query->where('query', 'like', "%{$term}%");
    }
}