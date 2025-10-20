<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ExternalChatbotController extends Controller
{
    /**
     * Display the external chatbot iframe
     */
    public function frame(string $uuid)
    {
        // TODO: Implement chatbot lookup from database
        $chatbot = [
            'uuid' => $uuid,
            'name' => 'ChatCommerce Tause',
            'color_scheme' => '#007ACC',
            'welcome_message' => '¡Hola! ¿En qué puedo ayudarte hoy?',
        ];

        $routes = [
            'chat' => route('api.chatbot.chat', ['uuid' => $uuid]),
            'chatcommerce_chat' => route('api.chatcommerce.chat'),
            'chatcommerce_start_purchase' => route('api.chatcommerce.start-purchase'),
            'chatcommerce_customer_info' => route('api.chatcommerce.customer-info'),
            'chatcommerce_create_order' => route('api.chatcommerce.create-order'),
        ];

        return view('external-chatbot.frame', [
            'chatbot' => $chatbot,
            'routes' => $routes,
        ]);
    }

    /**
     * Get chatbot configuration
     */
    public function config(string $uuid): JsonResponse
    {
        // TODO: Implement chatbot lookup from database
        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $uuid,
                'name' => 'ChatCommerce Tause',
                'color_scheme' => '#007ACC',
                'welcome_message' => '¡Hola! ¿En qué puedo ayudarte hoy?',
                'settings' => [
                    'enable_sound' => true,
                    'enable_gdpr' => true,
                    'enable_chatcommerce' => true,
                ],
            ],
        ]);
    }

    /**
     * Handle chat messages
     */
    public function chat(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|string',
        ]);

        $sessionId = $request->input('session_id', Str::uuid()->toString());

        // TODO: Integrate with AI service (Neuron AI)
        $response = $this->generateAIResponse($request->input('message'));

        return response()->json([
            'success' => true,
            'data' => [
                'message' => $response,
                'session_id' => $sessionId,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get chatbot triggers
     */
    public function triggers(string $uuid): JsonResponse
    {
        // TODO: Implement trigger system
        return response()->json([
            'success' => true,
            'triggers' => [
                [
                    'id' => 'welcome',
                    'type' => 'page_load',
                    'delay' => 2000,
                    'message' => '¡Bienvenido! ¿Necesitas ayuda?',
                ],
            ],
        ]);
    }

    /**
     * Enable/disable sound for a session
     */
    public function enableSound(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        // TODO: Store preference in database

        return response()->json([
            'success' => true,
            'enabled_sound' => $request->input('enabled'),
        ]);
    }

    /**
     * Get conversation history
     */
    public function history(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        // TODO: Fetch from database

        return response()->json([
            'success' => true,
            'messages' => [],
        ]);
    }

    /**
     * Store a new message in history
     */
    public function storeMessage(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'message' => 'required|string',
            'type' => 'required|in:user,assistant',
        ]);

        // TODO: Store in database

        return response()->json([
            'success' => true,
            'message' => 'Message stored successfully',
        ]);
    }

    /**
     * Generate AI response (placeholder)
     */
    private function generateAIResponse(string $message): string
    {
        // TODO: Integrate with Neuron AI

        // Simple placeholder responses
        $responses = [
            '¡Hola! Estoy aquí para ayudarte. ¿Qué producto estás buscando?',
            'Claro, déjame ayudarte con eso.',
            'Entiendo. ¿Puedo ayudarte con algo más?',
            'Perfecto, ¿necesitas información sobre algún producto en particular?',
        ];

        return $responses[array_rand($responses)];
    }
}

