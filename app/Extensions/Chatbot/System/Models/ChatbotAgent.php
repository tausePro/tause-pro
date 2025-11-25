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
        'is_enabled'    => 'boolean',
        'priority'      => 'integer',
        'configuration' => 'array',
        'triggers'      => 'array',
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
     * Verificar si el agente debe activarse según el contexto (Mejorado)
     *
     * @param  string  $userQuery  Query del usuario
     * @param  string  $aiResponse  Respuesta del AI
     * @param  array  $intent  Análisis de intención del AgentIntelligenceService
     * @param  array  $context  Contexto adicional
     */
    public function shouldActivate(
        string $userQuery,
        string $aiResponse = '',
        array $intent = [],
        array $context = []
    ): bool {
        if (! $this->is_enabled) {
            return false;
        }

        $triggers = $this->triggers ?? [];

        // Si es "always_active", siempre se activa (para External Agent)
        if (isset($triggers['always_active']) && $triggers['always_active']) {
            return true;
        }

        // Si el agente es "external" y no tiene triggers específicos, activarse por defecto
        if ($this->agent_type === 'external' && empty($triggers)) {
            return true;
        }

        // Keywords a verificar
        $keywords = $triggers['keywords'] ?? [];
        
        // Para Sales Agent, agregar keywords por defecto si no hay configurados
        if ($this->agent_type === 'sales' && empty($keywords)) {
            $keywords = [
                'comprar', 'quiero comprar', 'me interesa comprar',
                'ver productos', 'mostrar productos', 'qué productos',
                'catálogo', 'ver catálogo',
                'precio', 'cuánto cuesta', 'cuanto cuesta',
                'tienen', 'venden',
            ];
        }
        
        // Si tiene keywords, verificar si alguno está presente en el query del USUARIO
        // IMPORTANTE: Solo verificar el userQuery, NO el aiResponse
        // Esto evita que el bot se active porque el AI menciona productos
        if (! empty($keywords)) {
            $text = strtolower($userQuery);

            foreach ($keywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    return true;
                }
            }
        }

        // Si detecta intención comercial (solo si está explícitamente configurado)
        // NOTA: Deshabilitado por defecto para evitar activación agresiva.
        // El Sales Agent solo debe activarse con keywords explícitos.
        if (isset($triggers['detect_commercial_intent']) && $triggers['detect_commercial_intent']) {
            // Solo usar análisis de intención con alta confianza
            if (! empty($intent) && isset($intent['type']) && $intent['type'] === 'commercial') {
                // Requiere al menos 70% de confianza para activar automáticamente
                return ($intent['confidence'] ?? 0) >= 70;
            }
            // NO usar fallback a detección tradicional - es muy agresivo
        }

        // Para Sales Agent, NO activar automáticamente por intención comercial
        // Solo activar si el usuario usa keywords explícitos (manejado arriba)
        // Esto mantiene la conversación natural y el usuario en control

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
