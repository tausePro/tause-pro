<?php

namespace App\Extensions\Chatbot\System\Models;

use App\Extensions\Chatbot\System\Enums\ColorModeEnum;
use App\Extensions\Chatbot\System\Enums\InteractionType;
use App\Extensions\Chatbot\System\Enums\PositionEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Chatbot extends Model
{
    protected $table = 'ext_chatbots';

    protected $fillable = [
        'uuid',
        'user_id',
        'interaction_type',
        'title',
        'bubble_message',
        'welcome_message',
        'connect_message',
        'instructions',
        'do_not_go_beyond_instructions',
        'language',
        'ai_model',
        'ai_embedding_model',
        'limit_per_minute',
        'show_pre_defined_questions',
        'pre_defined_questions',
        // customization start
        'logo',
        'avatar',
        'trigger_avatar_size',
        'trigger_background',
        'trigger_foreground',
        'color_mode',
        'color',
        'show_logo',
        // welcome customization
        'welcome_background',
        'welcome_greeting',
        'welcome_subtitle',
        'welcome_button_text',
        'welcome_button_subtitle',
        'show_date_and_time',
        'show_average_response_time',
        'position',
        // customization end
        'active',
        'footer_link',
        'is_demo',
        'is_favorite',
        // new options
        'is_email_collect',
        'is_contact',
        'is_attachment',
        'is_emoji',
        'is_articles',
        'is_links',
        // links
        'whatsapp_link',
        'telegram_link',
        'watch_product_tour_link',
    ];

    protected $casts = [
        'color_mode'                    => ColorModeEnum::class,
        'position'                      => PositionEnum::class,
        'interaction_type'              => InteractionType::class,
        'do_not_go_beyond_instructions' => 'boolean',
        'limit_per_minute'              => 'integer',
        'show_pre_defined_questions'    => 'boolean',
        'pre_defined_questions'         => 'array',
        'active'                        => 'boolean',
        'user_id'                       => 'integer',
        'is_demo'                       => 'boolean',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class, 'chatbot_id', 'id');
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(ChatbotEmbedding::class, 'chatbot_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function channels(): HasMany
    {
        return $this->hasMany(ChatbotChannel::class, 'chatbot_id', 'id');
    }

    public function articles(): \Illuminate\Database\Eloquent\Collection|array
    {
        return ChatbotKnowledgeBaseArticle::query()
            ->whereRaw('JSON_CONTAINS(chatbots, ?)', ['"' . $this->getKey() . '"'])
            ->select(columns: [
                'id',
                'title',
                'description as excerpt',
                'is_featured',
                DB::raw('"#" as link'),
            ])
            ->get();
    }

    /**
     * Get all products associated with this chatbot.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            ChatbotProduct::class,
            'ext_chatbot_product_pivot',
            'chatbot_id',
            'product_id'
        )->withTimestamps();
    }

    /**
     * Get all social content associated with this chatbot.
     */
    public function socialContent(): HasMany
    {
        return $this->hasMany(ChatbotSocialContent::class, 'chatbot_id', 'id');
    }

    /**
     * Get analytics data for this chatbot.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(ChatbotAnalytics::class, 'chatbot_id', 'id');
    }

    /**
     * Get WhatsApp Business configuration for this chatbot.
     */
    public function whatsappBusiness(): HasMany
    {
        return $this->hasMany(ChatbotWhatsAppBusiness::class, 'chatbot_id', 'id');
    }

    /**
     * Get triggers for this chatbot.
     */
    public function triggers(): HasMany
    {
        return $this->hasMany(ChatbotTrigger::class, 'chatbot_id', 'id');
    }

    /**
     * Get knowledge base articles with enhanced relationships.
     */
    public function knowledgeBaseArticles(): HasMany
    {
        return $this->hasMany(ChatbotKnowledgeBaseArticle::class)
                    ->whereRaw('JSON_CONTAINS(chatbots, ?)', ['"' . $this->getKey() . '"']);
    }

    /**
     * Get products that are in stock.
     */
    public function availableProducts(): HasMany
    {
        return $this->products()->available()->inStock();
    }

    /**
     * Get recent social media content.
     */
    public function recentSocialContent(): HasMany
    {
        return $this->socialContent()->recent();
    }

    /**
     * Get analytics for a specific date range.
     */
    public function analyticsForPeriod($startDate, $endDate): HasMany
    {
        return $this->analytics()->betweenDates($startDate, $endDate);
    }

    /**
     * Get active WhatsApp Business account.
     */
    public function activeWhatsAppBusiness()
    {
        return $this->whatsappBusiness()->active()->configured()->first();
    }

    /**
     * Check if chatbot has products configured.
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Check if chatbot has social media integration.
     */
    public function hasSocialIntegration(): bool
    {
        return $this->socialContent()->exists();
    }

    /**
     * Check if chatbot has WhatsApp Business integration.
     */
    public function hasWhatsAppBusiness(): bool
    {
        return $this->whatsappBusiness()->configured()->exists();
    }

    /**
     * Get total number of conversations.
     */
    public function getTotalConversationsAttribute(): int
    {
        return $this->conversations()->count();
    }

    /**
     * Get average user satisfaction.
     */
    public function getAverageSatisfactionAttribute(): ?float
    {
        return $this->analytics()
                    ->whereNotNull('user_satisfaction')
                    ->avg('user_satisfaction');
    }

    /**
     * Get conversion rate.
     */
    public function getConversionRateAttribute(): float
    {
        $total = $this->analytics()->count();
        $conversions = $this->analytics()->conversions()->count();
        
        return $total > 0 ? round(($conversions / $total) * 100, 2) : 0;
    }

    /**
     * Get active triggers for this chatbot.
     */
    public function activeTriggers(): HasMany
    {
        return $this->triggers()->active()->byPriority();
    }

    /**
     * Check if chatbot has triggers configured.
     */
    public function hasTriggers(): bool
    {
        return $this->triggers()->exists();
    }

    /**
     * Check if chatbot has active triggers.
     */
    public function hasActiveTriggers(): bool
    {
        return $this->triggers()->active()->exists();
    }

    /**
     * Get trigger by type.
     */
    public function getTriggerByType(string $type): ?ChatbotTrigger
    {
        return $this->triggers()->byType($type)->active()->first();
    }
}
