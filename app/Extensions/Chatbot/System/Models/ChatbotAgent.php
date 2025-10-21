<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para agentes del chatbot
 * 
 * Representa los diferentes agentes que pueden orquestarse en un chatbot:
 * - External Chatbot (conversacional)
 * - Sales Agent (ventas)
 * - Support Agent (soporte)
 * - Appointment Agent (citas)
 * etc.
 */
class ChatbotAgent extends Model
{
    protected $table = 'ext_chatbot_agents';

    protected $fillable = [
        'chatbot_id',
        'agent_type',
        'name',
        'description',
        'is_enabled',
        'priority',
        'configuration',
        'triggers',
        'pricing_tier',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'priority' => 'integer',
        'configuration' => 'array',
        'triggers' => 'array',
    ];

    /**
     * Relación con el chatbot
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }

    /**
     * Scope: Solo agentes activos
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Por tipo de agente
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('agent_type', $type);
    }

    /**
     * Scope: Ordenar por prioridad (mayor primero)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Verificar si el agente debe activarse según el contexto
     */
    public function shouldActivate(string $userQuery, string $aiResponse = ''): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        $triggers = $this->triggers ?? [];

        // Si es "always_active", siempre se activa
        if (isset($triggers['always_active']) && $triggers['always_active']) {
            return true;
        }

        // Si tiene keywords, verificar si alguno está presente
        if (isset($triggers['keywords']) && is_array($triggers['keywords'])) {
            $text = strtolower($userQuery . ' ' . $aiResponse);
            
            foreach ($triggers['keywords'] as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    return true;
                }
            }
        }

        // Si detecta intención comercial
        if (isset($triggers['detect_commercial_intent']) && $triggers['detect_commercial_intent']) {
            return $this->detectCommercialIntent($userQuery, $aiResponse);
        }

        return false;
    }

    /**
     * Detectar intención comercial en el texto
     */
    protected function detectCommercialIntent(string $userQuery, string $aiResponse): bool
    {
        $commercialPhrases = [
            'te recomiendo', 'tenemos disponible', 'contamos con',
            'puedes adquirir', 'puedes comprar', 'está en', 'cuesta',
            'precio de', 'valor de', 'te ofrecemos', 'ideal para',
        ];

        $text = strtolower($userQuery . ' ' . $aiResponse);

        foreach ($commercialPhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener configuración específica
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->configuration[$key] ?? $default;
    }

    /**
     * Verificar si requiere un plan premium
     */
    public function isPremium(): bool
    {
        return in_array($this->pricing_tier, ['premium', 'enterprise']);
    }

    /**
     * Obtener agentes activos de un chatbot ordenados por prioridad
     */
    public static function getActiveAgents(int $chatbotId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('chatbot_id', $chatbotId)
            ->enabled()
            ->byPriority()
            ->get();
    }
}
