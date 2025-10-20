<?php

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotTrigger;
use App\Extensions\Chatbot\System\Services\ProactiveTriggerService;
use App\Extensions\Chatbot\System\Services\TriggerAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatbotTriggerController extends Controller
{
    public function __construct(
        protected ProactiveTriggerService $triggerService,
        protected TriggerAnalyticsService $analyticsService
    ) {}

    /**
     * Get triggers for a chatbot
     */
    public function index(Request $request, string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Detect industry based on chatbot products
        $detectedIndustry = $this->detectChatbotIndustry($chatbot);
        
        $triggers = $chatbot->triggers()
            ->orderBy('priority', 'asc')
            ->get()
            ->keyBy('trigger_type');

        // Get triggers relevant to this industry
        $availableTriggers = $this->getTriggersForIndustry($detectedIndustry);

        // Convert to the format expected by the frontend
        $formattedTriggers = [];
        foreach ($availableTriggers as $type => $default) {
            $trigger = $triggers->get($type);
            $formattedTriggers[$type] = [
                'enabled' => $trigger ? $trigger->is_active : false,
                'message' => $trigger ? $trigger->message_template : $default['message'],
                'category' => $default['category'],
                'industry_specific' => $default['category'] !== 'universal'
            ];
        }

        return response()->json([
            'triggers' => $formattedTriggers,
            'detected_industry' => $detectedIndustry,
            'settings' => [
                'max_per_session' => 3,
                'cooldown_minutes' => 5
            ]
        ]);
    }

    /**
     * Detect chatbot industry based on products and content
     */
    protected function detectChatbotIndustry(Chatbot $chatbot): ?string
    {
        // Get product categories and names
        $products = $chatbot->products()->with('category')->get();
        
        if ($products->isEmpty()) {
            return null;
        }

        $productText = $products->map(function($product) {
            return strtolower($product->name . ' ' . ($product->category->name ?? '') . ' ' . ($product->description ?? ''));
        })->implode(' ');

        // Industry detection keywords
        $industryKeywords = [
            'beauty' => ['crema', 'serum', 'skincare', 'facial', 'piel', 'belleza', 'cosmetic', 'maquillaje', 'limpiador', 'hidratante', 'protector solar'],
            'fashion' => ['ropa', 'vestido', 'camisa', 'pantalon', 'zapatos', 'moda', 'estilo', 'talla', 'outfit'],
            'technology' => ['smartphone', 'laptop', 'tablet', 'electronico', 'gadget', 'tech', 'digital', 'software'],
            'food' => ['comida', 'alimento', 'bebida', 'restaurant', 'cocina', 'ingrediente', 'receta'],
            'health' => ['salud', 'medicina', 'vitamina', 'suplemento', 'fitness', 'ejercicio', 'bienestar'],
            'real_estate' => ['casa', 'apartamento', 'propiedad', 'inmueble', 'alquiler', 'venta']
        ];

        // Count matches for each industry
        $industryScores = [];
        foreach ($industryKeywords as $industry => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($productText, $keyword);
            }
            $industryScores[$industry] = $score;
        }

        // Return industry with highest score, or null if no clear match
        $maxScore = max($industryScores);
        if ($maxScore > 0) {
            return array_search($maxScore, $industryScores);
        }

        return null;
    }

    /**
     * Save triggers for a chatbot
     */
    public function store(Request $request, string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'triggers' => 'required|array',
            'triggers.*.enabled' => 'boolean',
            'triggers.*.message' => 'required|string|max:500',
            'settings' => 'array',
            'settings.max_per_session' => 'integer|min:1|max:10',
            'settings.cooldown_minutes' => 'integer|min:1|max:60'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $triggers = $request->input('triggers', []);
        $settings = $request->input('settings', []);

        // Save each trigger
        foreach ($triggers as $triggerType => $triggerData) {
            if (!$this->isValidTriggerType($triggerType)) {
                continue;
            }

            $trigger = ChatbotTrigger::updateOrCreate(
                [
                    'chatbot_id' => $chatbot->id,
                    'trigger_type' => $triggerType
                ],
                [
                    'name' => $this->getTriggerName($triggerType),
                    'message_template' => $triggerData['message'],
                    'is_active' => $triggerData['enabled'],
                    'action' => $this->getTriggerAction($triggerType),
                    'priority' => $this->getTriggerPriority($triggerType),
                    'cooldown_minutes' => $settings['cooldown_minutes'] ?? 5,
                    'frequency_limit' => $settings['max_per_session'] ?? 3,
                    'conditions' => $this->getTriggerConditions($triggerType),
                    'display_config' => $this->getTriggerDisplayConfig($triggerType)
                ]
            );
        }

        // Clear trigger cache
        $this->triggerService->clearTriggerCache($chatbot->id);

        return response()->json([
            'message' => 'Triggers saved successfully',
            'triggers_count' => count(array_filter($triggers, fn($t) => $t['enabled']))
        ]);
    }

    /**
     * Get active triggers for a chatbot (API endpoint)
     */
    public function getActiveTriggers(string $chatbotId): JsonResponse
    {
        $triggers = $this->triggerService->getActiveTriggers($chatbotId);

        return response()->json([
            'triggers' => $triggers
        ]);
    }

    /**
     * Evaluate a trigger
     */
    public function evaluateTrigger(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chatbot_id' => 'required|string',
            'trigger_type' => 'required|string',
            'context' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->triggerService->evaluateTrigger(
            $request->input('trigger_type'),
            $request->input('context'),
            $request->input('chatbot_id')
        );

        if ($result) {
            return response()->json([
                'trigger' => $result
            ]);
        }

        return response()->json([
            'trigger' => null
        ]);
    }

    /**
     * Record trigger analytics
     */
    public function recordAnalytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'events' => 'required|array',
            'events.*.event_type' => 'required|string',
            'events.*.trigger_id' => 'required|integer',
            'events.*.session_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $events = $request->input('events', []);
        
        foreach ($events as $event) {
            switch ($event['event_type']) {
                case 'trigger_response':
                    $this->analyticsService->recordTriggerResponse(
                        $event['trigger_id'],
                        $event['session_id'],
                        $event['response_type'] ?? 'unknown',
                        $event['conversion_achieved'] ?? false,
                        $event['conversion_value'] ?? null
                    );
                    break;
            }
        }

        return response()->json([
            'message' => 'Analytics recorded successfully'
        ]);
    }

    /**
     * Get trigger analytics
     */
    public function getAnalytics(string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $summary = $this->analyticsService->getChatbotTriggerSummary($chatbot->id);
        $topPerforming = $this->analyticsService->getTopPerformingTriggers($chatbot->id);

        return response()->json([
            'summary' => $summary,
            'top_performing' => $topPerforming
        ]);
    }

    /**
     * Get default triggers configuration
     */
    protected function getDefaultTriggers(): array
    {
        // Base triggers that apply to all businesses
        $baseTriggers = [
            'welcome_30s' => [
                'message' => '¡Hola! ¿Te puedo ayudar a encontrar algo específico? 😊',
                'action' => 'show_message',
                'priority' => 1,
                'category' => 'universal'
            ],
            'exit_intent' => [
                'message' => '¡Espera! ¿Te gustaría un {discount_amount} de descuento?',
                'action' => 'show_discount',
                'priority' => 2,
                'category' => 'universal'
            ],
            'page_dwell_2min' => [
                'message' => '¿Necesitas ayuda con algo específico?',
                'action' => 'show_message',
                'priority' => 3,
                'category' => 'universal'
            ],
            'product_page' => [
                'message' => '¿Te interesa {product_name}? Puedo contarte más detalles',
                'action' => 'show_message',
                'priority' => 4,
                'category' => 'universal'
            ],
            'category_page' => [
                'message' => '¿Buscas algo específico en {category_name}?',
                'action' => 'show_message',
                'priority' => 4,
                'category' => 'universal'
            ],
            'checkout_page' => [
                'message' => '¿Alguna duda sobre el proceso de compra?',
                'action' => 'show_message',
                'priority' => 2,
                'category' => 'universal'
            ],
            'first_visitor_discount' => [
                'message' => '¡Bienvenido! Como nuevo cliente, tienes un 15% de descuento',
                'action' => 'show_discount',
                'priority' => 1,
                'category' => 'universal'
            ],
            'cart_abandonment' => [
                'message' => '¿Necesitas ayuda para completar tu compra?',
                'action' => 'show_message',
                'priority' => 2,
                'category' => 'universal'
            ],
            'search_no_results' => [
                'message' => 'No encontré resultados para \'{search_term}\'. ¿Te ayudo a buscar algo similar?',
                'action' => 'show_message',
                'priority' => 3,
                'category' => 'universal'
            ]
        ];

        // Industry-specific triggers
        $industryTriggers = [
            // Beauty & Skincare
            'skincare_consultation' => [
                'message' => '¿Conoces tu tipo de piel? Puedo recomendarte la rutina perfecta',
                'action' => 'start_consultation',
                'priority' => 5,
                'category' => 'beauty',
                'industries' => ['beauty', 'skincare', 'cosmetics']
            ],
            'skin_type_quiz' => [
                'message' => '¿Te gustaría hacer un quiz rápido para conocer tu tipo de piel?',
                'action' => 'start_consultation',
                'priority' => 6,
                'category' => 'beauty',
                'industries' => ['beauty', 'skincare', 'cosmetics']
            ],
            
            // Fashion & Clothing
            'style_consultation' => [
                'message' => '¿Buscas un look específico? Puedo ayudarte a encontrar el estilo perfecto',
                'action' => 'start_consultation',
                'priority' => 5,
                'category' => 'fashion',
                'industries' => ['fashion', 'clothing', 'apparel']
            ],
            'size_guide' => [
                'message' => '¿Necesitas ayuda con las tallas? Puedo guiarte para encontrar el ajuste perfecto',
                'action' => 'show_message',
                'priority' => 4,
                'category' => 'fashion',
                'industries' => ['fashion', 'clothing', 'apparel']
            ],
            
            // Technology & Electronics
            'tech_support' => [
                'message' => '¿Tienes dudas técnicas sobre este producto? Puedo ayudarte con especificaciones',
                'action' => 'show_message',
                'priority' => 4,
                'category' => 'technology',
                'industries' => ['technology', 'electronics', 'gadgets']
            ],
            'compatibility_check' => [
                'message' => '¿Quieres verificar la compatibilidad con tus dispositivos actuales?',
                'action' => 'start_consultation',
                'priority' => 5,
                'category' => 'technology',
                'industries' => ['technology', 'electronics', 'gadgets']
            ],
            
            // Food & Restaurants
            'dietary_consultation' => [
                'message' => '¿Tienes alguna restricción alimentaria? Puedo recomendarte opciones adecuadas',
                'action' => 'start_consultation',
                'priority' => 5,
                'category' => 'food',
                'industries' => ['food', 'restaurant', 'catering']
            ],
            'menu_recommendations' => [
                'message' => '¿No sabes qué elegir? Puedo recomendarte nuestros platos más populares',
                'action' => 'show_message',
                'priority' => 4,
                'category' => 'food',
                'industries' => ['food', 'restaurant', 'catering']
            ],
            
            // Real Estate
            'property_consultation' => [
                'message' => '¿Buscas una propiedad específica? Puedo ayudarte según tus necesidades',
                'action' => 'start_consultation',
                'priority' => 5,
                'category' => 'real_estate',
                'industries' => ['real_estate', 'property', 'housing']
            ],
            
            // Health & Wellness
            'wellness_consultation' => [
                'message' => '¿Te gustaría una consulta personalizada sobre bienestar?',
                'action' => 'start_consultation',
                'priority' => 5,
                'category' => 'health',
                'industries' => ['health', 'wellness', 'fitness']
            ]
        ];

        return array_merge($baseTriggers, $industryTriggers);
    }

    /**
     * Get triggers filtered by industry/category
     */
    protected function getTriggersForIndustry(?string $industry = null): array
    {
        $allTriggers = $this->getDefaultTriggers();
        
        if (!$industry) {
            // Return only universal triggers if no industry specified
            return array_filter($allTriggers, fn($trigger) => $trigger['category'] === 'universal');
        }
        
        // Return universal triggers + industry-specific triggers
        return array_filter($allTriggers, function($trigger) use ($industry) {
            return $trigger['category'] === 'universal' || 
                   (isset($trigger['industries']) && in_array($industry, $trigger['industries']));
        });
    }

    /**
     * Check if trigger type is valid
     */
    protected function isValidTriggerType(string $type): bool
    {
        return array_key_exists($type, $this->getDefaultTriggers());
    }

    /**
     * Get trigger name
     */
    protected function getTriggerName(string $type): string
    {
        $names = [
            'welcome_30s' => 'Welcome Message (30s)',
            'exit_intent' => 'Exit Intent Offer',
            'page_dwell_2min' => 'Long Page Dwell (2min)',
            'product_page' => 'Product Page Assistance',
            'category_page' => 'Category Page Guidance',
            'checkout_page' => 'Checkout Support',
            'first_visitor_discount' => 'First Visitor Discount (15%)',
            'cart_abandonment' => 'Cart Abandonment Recovery',
            'skincare_consultation' => 'Skincare Consultation',
            'search_no_results' => 'Search No Results'
        ];

        return $names[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get trigger action
     */
    protected function getTriggerAction(string $type): string
    {
        $defaults = $this->getDefaultTriggers();
        return $defaults[$type]['action'] ?? 'show_message';
    }

    /**
     * Get trigger priority
     */
    protected function getTriggerPriority(string $type): int
    {
        $defaults = $this->getDefaultTriggers();
        return $defaults[$type]['priority'] ?? 5;
    }

    /**
     * Get trigger conditions
     */
    protected function getTriggerConditions(string $type): array
    {
        return match($type) {
            'product_page' => [
                ['type' => 'page_type', 'operator' => 'equals', 'value' => 'product']
            ],
            'category_page' => [
                ['type' => 'page_type', 'operator' => 'equals', 'value' => 'category']
            ],
            'checkout_page' => [
                ['type' => 'page_type', 'operator' => 'equals', 'value' => 'checkout']
            ],
            'first_visitor_discount' => [
                ['type' => 'is_new_visitor', 'operator' => 'equals', 'value' => true]
            ],
            'skincare_consultation' => [
                ['type' => 'product_category', 'operator' => 'contains', 'value' => 'skincare']
            ],
            default => []
        };
    }

    /**
     * Get trigger display configuration
     */
    protected function getTriggerDisplayConfig(string $type): array
    {
        return [
            'position' => 'bottom-right',
            'theme' => 'default',
            'animation' => 'slide-up',
            'auto_hide_delay' => null
        ];
    }

    /**
     * Show trigger management page
     */
    public function managementPage(string $chatbotId)
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('chatbot::management.triggers', compact('chatbot'));
    }

    /**
     * Get custom triggers for a chatbot
     */
    public function getCustomTriggers(string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $customTriggers = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->where('trigger_type', 'LIKE', 'custom_%')
            ->orWhere('is_custom', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'triggers' => $customTriggers->map(function($trigger) {
                return [
                    'id' => $trigger->id,
                    'name' => $trigger->trigger_name ?? $trigger->trigger_type,
                    'message' => $trigger->message_template,
                    'type' => $trigger->trigger_type,
                    'is_active' => $trigger->is_active,
                    'created_at' => $trigger->created_at->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }

    /**
     * Store a new custom trigger
     */
    public function storeCustomTrigger(Request $request, string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|max:100',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $trigger = ChatbotTrigger::create([
            'chatbot_id' => $chatbotId,
            'trigger_name' => $request->input('name'),
            'trigger_type' => 'custom_' . $request->input('type'),
            'message_template' => $request->input('message'),
            'is_active' => $request->boolean('is_active', true),
            'is_custom' => true,
            'conditions' => [],
            'display_config' => $this->getDefaultDisplayConfig(),
            'frequency_config' => [],
            'priority' => 5
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Custom trigger created successfully',
            'trigger' => [
                'id' => $trigger->id,
                'name' => $trigger->trigger_name,
                'message' => $trigger->message_template,
                'type' => $trigger->trigger_type,
                'is_active' => $trigger->is_active
            ]
        ]);
    }

    /**
     * Update a custom trigger
     */
    public function updateCustomTrigger(Request $request, string $chatbotId, int $triggerId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->where('id', $triggerId)
            ->where(function($query) {
                $query->where('trigger_type', 'LIKE', 'custom_%')
                      ->orWhere('is_custom', true);
            })
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|max:100',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $trigger->update([
            'trigger_name' => $request->input('name'),
            'trigger_type' => 'custom_' . $request->input('type'),
            'message_template' => $request->input('message'),
            'is_active' => $request->boolean('is_active')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Custom trigger updated successfully',
            'trigger' => [
                'id' => $trigger->id,
                'name' => $trigger->trigger_name,
                'message' => $trigger->message_template,
                'type' => $trigger->trigger_type,
                'is_active' => $trigger->is_active
            ]
        ]);
    }

    /**
     * Delete a custom trigger
     */
    public function deleteCustomTrigger(string $chatbotId, int $triggerId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->where('id', $triggerId)
            ->where(function($query) {
                $query->where('trigger_type', 'LIKE', 'custom_%')
                      ->orWhere('is_custom', true);
            })
            ->firstOrFail();

        $trigger->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom trigger deleted successfully'
        ]);
    }
}