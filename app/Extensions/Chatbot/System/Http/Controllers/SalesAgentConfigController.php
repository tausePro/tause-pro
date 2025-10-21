<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\SalesAgentConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SalesAgentConfigController extends Controller
{
    /**
     * Obtener configuración actual del Sales Agent
     */
    public function show(Chatbot $chatbot): JsonResponse
    {
        // Verificar autorización
        if ($chatbot->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $config = $chatbot->salesAgentConfig ?? new SalesAgentConfig();

        return response()->json([
            'success' => true,
            'config' => $config->getFullConfig(),
        ]);
    }

    /**
     * Guardar configuración del Sales Agent
     */
    public function update(Request $request, Chatbot $chatbot): JsonResponse
    {
        // Verificar autorización
        if ($chatbot->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Validar datos
        $validated = $request->validate([
            'enabled' => 'boolean',
            'agent_name' => 'required|string|max:100',
            'agent_description' => 'nullable|string|max:500',
            'tone' => 'required|in:formal,casual,friendly',
            'sales_strategy' => 'required|in:consultative,aggressive,helpful',
            'search_strategy' => 'required|in:keyword,semantic,hybrid',
            'product_display_mode' => 'required|in:conversational,cards,both',
            'custom_prompt' => 'nullable|string|max:2000',
            'auto_activate' => 'boolean',
            'activation_keywords' => 'nullable|array',
            'activation_keywords.*' => 'string',
            'product_card_config' => 'nullable|array',
            'product_card_config.button_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.button_text_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.button_style' => 'nullable|in:solid,gradient,outline',
            'product_card_config.card_border_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.card_shadow' => 'nullable|in:none,sm,md,lg',
            'product_card_config.price_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.discount_badge_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_card_config.show_stock_indicator' => 'nullable|boolean',
            'product_card_config.show_discount_badge' => 'nullable|boolean',
        ]);

        // Obtener o crear configuración
        $config = $chatbot->salesAgentConfig ?? new SalesAgentConfig();
        $config->chatbot_id = $chatbot->id;
        $config->fill($validated);
        $config->save();

        return response()->json([
            'success' => true,
            'message' => 'Sales Agent configuration saved successfully',
            'config' => $config->getFullConfig(),
        ]);
    }

    /**
     * Preview de conversación (opcional)
     */
    public function preview(Request $request, Chatbot $chatbot): JsonResponse
    {
        // Verificar autorización
        if ($chatbot->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $userMessage = $request->input('message', 'Estoy buscando muletas');
        $config = $chatbot->salesAgentConfig;

        // Aquí iría la lógica de preview con IA
        // Por ahora retornamos un ejemplo

        return response()->json([
            'success' => true,
            'user_message' => $userMessage,
            'agent_response' => "¡Hola! Tengo varias opciones de muletas que podrían interesarte. ¿Quieres que te ayude a encontrar exactamente lo que necesitas?",
            'products_found' => 3,
        ]);
    }
}
