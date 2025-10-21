<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAgentConfig extends Model
{
    protected $table = 'ext_chatbot_sales_agent_configs';

    protected $fillable = [
        'chatbot_id',
        'enabled',
        'agent_name',
        'agent_description',
        'tone',
        'sales_strategy',
        'search_strategy',
        'product_display_mode',
        'custom_prompt',
        'auto_activate',
        'activation_keywords',
        'product_card_config',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_activate' => 'boolean',
        'activation_keywords' => 'array',
        'product_card_config' => 'array',
    ];

    /**
     * Relación con Chatbot
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }

    /**
     * Obtener configuración de tarjeta con valores por defecto
     */
    public function getProductCardConfig(): array
    {
        return $this->product_card_config ?? [
            'button_color' => '#10b981',
            'button_text_color' => '#ffffff',
            'button_style' => 'solid',
            'card_border_color' => '#e5e7eb',
            'card_shadow' => 'md',
            'card_border_radius' => '0.75rem',
            'price_color' => '#10b981',
            'discount_badge_color' => '#ef4444',
            'show_stock_indicator' => true,
            'show_discount_badge' => true,
        ];
    }

    /**
     * Obtener configuración completa con valores por defecto
     */
    public function getFullConfig(): array
    {
        return [
            'enabled' => $this->enabled ?? false,
            'agent_name' => $this->agent_name ?? 'Vendedor',
            'agent_description' => $this->agent_description,
            'tone' => $this->tone ?? 'friendly',
            'sales_strategy' => $this->sales_strategy ?? 'helpful',
            'search_strategy' => $this->search_strategy ?? 'semantic',
            'product_display_mode' => $this->product_display_mode ?? 'both',
            'custom_prompt' => $this->custom_prompt,
            'auto_activate' => $this->auto_activate ?? true,
            'activation_keywords' => $this->activation_keywords ?? [],
            'product_card_config' => $this->getProductCardConfig(),
        ];
    }
}
