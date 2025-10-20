<?php

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotTrigger;
use App\Extensions\Chatbot\System\Models\ChatbotTriggerAnalytic;
use App\Extensions\Chatbot\System\Services\TriggerAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TriggerManagementController extends Controller
{
    protected TriggerAnalyticsService $analyticsService;

    public function __construct(TriggerAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display trigger management dashboard
     */
    public function index(Request $request, int $chatbotId): View
    {
        $chatbot = Chatbot::findOrFail($chatbotId);
        
        // Get triggers with analytics
        $triggers = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->with(['analytics' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get performance metrics
        $performanceMetrics = $this->analyticsService->getTriggerPerformanceMetrics($chatbotId);
        
        // Get trigger categories for filtering
        $triggerCategories = [
            'time_based' => 'Time-based Triggers',
            'page_based' => 'Page-based Triggers',
            'behavior_based' => 'Behavior-based Triggers',
            'contextual' => 'Contextual Triggers',
            'discount' => 'Discount & Promotion Triggers'
        ];

        return view('chatbot.triggers.management.index', compact(
            'chatbot',
            'triggers',
            'performanceMetrics',
            'triggerCategories'
        ));
    }

    /**
     * Get triggers data for DataTable
     */
    public function getTriggersData(Request $request, int $chatbotId): JsonResponse
    {
        $query = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->with(['analytics' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }]);

        // Apply filters
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('trigger_category', $request->category);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('trigger_name', 'like', '%' . $request->search . '%')
                  ->orWhere('trigger_type', 'like', '%' . $request->search . '%')
                  ->orWhere('message_template', 'like', '%' . $request->search . '%');
            });
        }

        $triggers = $query->get();

        $data = $triggers->map(function ($trigger) {
            $analytics = $trigger->analytics;
            $totalDisplays = $analytics->sum('displays_count');
            $totalInteractions = $analytics->sum('interactions_count');
            $conversionRate = $totalDisplays > 0 ? ($totalInteractions / $totalDisplays) * 100 : 0;

            return [
                'id' => $trigger->id,
                'name' => $trigger->trigger_name,
                'type' => $trigger->trigger_type,
                'category' => $trigger->trigger_category,
                'status' => $trigger->is_active ? 'active' : 'inactive',
                'displays' => $totalDisplays,
                'interactions' => $totalInteractions,
                'conversion_rate' => round($conversionRate, 2),
                'last_triggered' => $trigger->last_triggered_at?->format('Y-m-d H:i:s'),
                'created_at' => $trigger->created_at->format('Y-m-d H:i:s'),
                'actions' => $this->getTriggerActions($trigger)
            ];
        });

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Show trigger creation form
     */
    public function create(int $chatbotId): View
    {
        $chatbot = Chatbot::findOrFail($chatbotId);
        
        $triggerTypes = $this->getTriggerTypes();
        $triggerCategories = $this->getTriggerCategories();
        $messageTemplates = $this->getMessageTemplates();

        return view('chatbot.triggers.management.create', compact(
            'chatbot',
            'triggerTypes',
            'triggerCategories',
            'messageTemplates'
        ));
    }

    /**
     * Store new trigger
     */
    public function store(Request $request, int $chatbotId): JsonResponse
    {
        $request->validate([
            'trigger_name' => 'required|string|max:255',
            'trigger_type' => 'required|string',
            'trigger_category' => 'required|string',
            'message_template' => 'required|string',
            'conditions' => 'required|array',
            'display_config' => 'array',
            'frequency_config' => 'array'
        ]);

        $trigger = ChatbotTrigger::create([
            'chatbot_id' => $chatbotId,
            'trigger_name' => $request->trigger_name,
            'trigger_type' => $request->trigger_type,
            'trigger_category' => $request->trigger_category,
            'message_template' => $request->message_template,
            'conditions' => $request->conditions,
            'display_config' => $request->display_config ?? [],
            'frequency_config' => $request->frequency_config ?? [],
            'is_active' => $request->boolean('is_active', true),
            'priority' => $request->integer('priority', 1)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trigger created successfully',
            'trigger' => $trigger
        ]);
    }

    /**
     * Show trigger edit form
     */
    public function edit(int $chatbotId, int $triggerId): View
    {
        $chatbot = Chatbot::findOrFail($chatbotId);
        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->findOrFail($triggerId);

        $triggerTypes = $this->getTriggerTypes();
        $triggerCategories = $this->getTriggerCategories();
        $messageTemplates = $this->getMessageTemplates();

        // Get trigger analytics for the edit form
        $analytics = $this->analyticsService->getTriggerAnalytics($triggerId, 30);

        return view('chatbot.triggers.management.edit', compact(
            'chatbot',
            'trigger',
            'triggerTypes',
            'triggerCategories',
            'messageTemplates',
            'analytics'
        ));
    }

    /**
     * Update trigger
     */
    public function update(Request $request, int $chatbotId, int $triggerId): JsonResponse
    {
        $request->validate([
            'trigger_name' => 'required|string|max:255',
            'trigger_type' => 'required|string',
            'trigger_category' => 'required|string',
            'message_template' => 'required|string',
            'conditions' => 'required|array',
            'display_config' => 'array',
            'frequency_config' => 'array'
        ]);

        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->findOrFail($triggerId);

        $trigger->update([
            'trigger_name' => $request->trigger_name,
            'trigger_type' => $request->trigger_type,
            'trigger_category' => $request->trigger_category,
            'message_template' => $request->message_template,
            'conditions' => $request->conditions,
            'display_config' => $request->display_config ?? [],
            'frequency_config' => $request->frequency_config ?? [],
            'is_active' => $request->boolean('is_active'),
            'priority' => $request->integer('priority', 1)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trigger updated successfully',
            'trigger' => $trigger
        ]);
    }

    /**
     * Toggle trigger status
     */
    public function toggleStatus(int $chatbotId, int $triggerId): JsonResponse
    {
        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->findOrFail($triggerId);

        $trigger->update([
            'is_active' => !$trigger->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trigger status updated successfully',
            'is_active' => $trigger->is_active
        ]);
    }

    /**
     * Duplicate trigger
     */
    public function duplicate(int $chatbotId, int $triggerId): JsonResponse
    {
        $originalTrigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->findOrFail($triggerId);

        $duplicatedTrigger = $originalTrigger->replicate();
        $duplicatedTrigger->trigger_name = $originalTrigger->trigger_name . ' (Copy)';
        $duplicatedTrigger->is_active = false; // Start as inactive
        $duplicatedTrigger->save();

        return response()->json([
            'success' => true,
            'message' => 'Trigger duplicated successfully',
            'trigger' => $duplicatedTrigger
        ]);
    }

    /**
     * Delete trigger
     */
    public function destroy(int $chatbotId, int $triggerId): JsonResponse
    {
        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->findOrFail($triggerId);

        $trigger->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trigger deleted successfully'
        ]);
    }

    /**
     * Test trigger preview
     */
    public function preview(Request $request, int $chatbotId): JsonResponse
    {
        $request->validate([
            'trigger_type' => 'required|string',
            'message_template' => 'required|string',
            'display_config' => 'array'
        ]);

        // Generate preview data
        $previewData = [
            'trigger_type' => $request->trigger_type,
            'message' => $this->processMessageTemplate($request->message_template),
            'display_config' => $request->display_config ?? [],
            'preview_context' => [
                'user_name' => 'Usuario Demo',
                'product_name' => 'Producto Demo',
                'discount_percentage' => 15,
                'cart_value' => 150000
            ]
        ];

        return response()->json([
            'success' => true,
            'preview' => $previewData
        ]);
    }

    /**
     * Test trigger functionality
     */
    public function test(Request $request, int $chatbotId, int $triggerId): JsonResponse
    {
        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->findOrFail($triggerId);

        // Simulate trigger execution
        $testResult = [
            'trigger_id' => $trigger->id,
            'trigger_name' => $trigger->trigger_name,
            'test_timestamp' => now()->toISOString(),
            'conditions_met' => true,
            'message_generated' => $this->processMessageTemplate($trigger->message_template),
            'display_config' => $trigger->display_config,
            'test_status' => 'success'
        ];

        return response()->json([
            'success' => true,
            'test_result' => $testResult
        ]);
    }

    /**
     * Get trigger templates
     */
    public function getTemplates(): JsonResponse
    {
        $templates = [
            'welcome' => [
                'name' => 'Welcome Message',
                'category' => 'time_based',
                'message' => '¡Hola! 👋 Bienvenido a nuestra tienda. ¿En qué puedo ayudarte hoy?',
                'conditions' => ['page_time' => 30],
                'display_config' => ['position' => 'bottom-right', 'animation' => 'slide-up']
            ],
            'cart_abandonment' => [
                'name' => 'Cart Abandonment',
                'category' => 'behavior_based',
                'message' => '¡No olvides tu carrito! 🛒 Tienes {{cart_items}} productos esperándote.',
                'conditions' => ['cart_time' => 300, 'cart_items' => 1],
                'display_config' => ['position' => 'center', 'animation' => 'bounce']
            ],
            'discount_offer' => [
                'name' => 'Discount Offer',
                'category' => 'discount',
                'message' => '🎉 ¡Oferta especial! Obtén {{discount_percentage}}% de descuento con el código {{discount_code}}',
                'conditions' => ['new_visitor' => true],
                'display_config' => ['position' => 'top-center', 'theme' => 'success']
            ]
        ];

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Apply template to create new trigger
     */
    public function applyTemplate(Request $request, int $chatbotId): JsonResponse
    {
        $request->validate([
            'template_key' => 'required|string',
            'trigger_name' => 'required|string'
        ]);

        $templates = $this->getTemplateData();
        $template = $templates[$request->template_key] ?? null;

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }

        $trigger = ChatbotTrigger::create([
            'chatbot_id' => $chatbotId,
            'trigger_name' => $request->trigger_name,
            'trigger_type' => $request->template_key,
            'trigger_category' => $template['category'],
            'message_template' => $template['message'],
            'conditions' => $template['conditions'],
            'display_config' => $template['display_config'],
            'is_active' => false // Start as inactive for review
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trigger created from template successfully',
            'trigger' => $trigger
        ]);
    }

    // Helper methods

    private function getTriggerActions(ChatbotTrigger $trigger): array
    {
        return [
            'edit' => route('chatbot.triggers.management.edit', [$trigger->chatbot_id, $trigger->id]),
            'toggle' => route('chatbot.triggers.management.toggle', [$trigger->chatbot_id, $trigger->id]),
            'duplicate' => route('chatbot.triggers.management.duplicate', [$trigger->chatbot_id, $trigger->id]),
            'test' => route('chatbot.triggers.management.test', [$trigger->chatbot_id, $trigger->id]),
            'delete' => route('chatbot.triggers.management.destroy', [$trigger->chatbot_id, $trigger->id])
        ];
    }

    private function getTriggerTypes(): array
    {
        return [
            'welcome_30s' => 'Welcome (30s)',
            'page_dwell_2min' => 'Page Dwell (2min)',
            'inactivity_5min' => 'Inactivity (5min)',
            'exit_intent' => 'Exit Intent',
            'cart_abandonment' => 'Cart Abandonment',
            'product_page' => 'Product Page Visit',
            'category_page' => 'Category Page Visit',
            'checkout_page' => 'Checkout Page Visit',
            'search_no_results' => 'Search No Results',
            'new_visitor_discount' => 'New Visitor Discount',
            'skincare_consultation' => 'Skincare Consultation',
            'luxury_product_consultation' => 'Luxury Product Consultation'
        ];
    }

    private function getTriggerCategories(): array
    {
        return [
            'time_based' => 'Time-based',
            'page_based' => 'Page-based',
            'behavior_based' => 'Behavior-based',
            'contextual' => 'Contextual',
            'discount' => 'Discount & Promotion'
        ];
    }

    private function getMessageTemplates(): array
    {
        return [
            'welcome' => '¡Hola! 👋 Bienvenido a {{site_name}}. ¿En qué puedo ayudarte?',
            'assistance' => 'Veo que has estado navegando por un tiempo. ¿Necesitas ayuda para encontrar algo específico?',
            'discount' => '🎉 ¡Oferta especial! Obtén {{discount_percentage}}% de descuento con el código {{discount_code}}',
            'cart_recovery' => '¡No olvides tu carrito! 🛒 Completa tu compra y obtén envío gratis.',
            'product_help' => '¿Tienes preguntas sobre {{product_name}}? Estoy aquí para ayudarte.',
            'consultation' => '¿Te gustaría una consulta personalizada? Puedo ayudarte a encontrar los productos perfectos para ti.'
        ];
    }

    private function processMessageTemplate(string $template): string
    {
        // Replace placeholders with demo data
        $replacements = [
            '{{site_name}}' => 'Mi Tienda',
            '{{user_name}}' => 'Usuario',
            '{{product_name}}' => 'Producto Demo',
            '{{discount_percentage}}' => '15',
            '{{discount_code}}' => 'DEMO15',
            '{{cart_items}}' => '3',
            '{{cart_value}}' => '$150.000'
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function getTemplateData(): array
    {
        return [
            'welcome' => [
                'name' => 'Welcome Message',
                'category' => 'time_based',
                'message' => '¡Hola! 👋 Bienvenido a nuestra tienda. ¿En qué puedo ayudarte hoy?',
                'conditions' => ['page_time' => 30],
                'display_config' => ['position' => 'bottom-right', 'animation' => 'slide-up']
            ],
            'cart_abandonment' => [
                'name' => 'Cart Abandonment',
                'category' => 'behavior_based',
                'message' => '¡No olvides tu carrito! 🛒 Tienes productos esperándote.',
                'conditions' => ['cart_time' => 300, 'cart_items' => 1],
                'display_config' => ['position' => 'center', 'animation' => 'bounce']
            ],
            'discount_offer' => [
                'name' => 'Discount Offer',
                'category' => 'discount',
                'message' => '🎉 ¡Oferta especial! Obtén 15% de descuento con el código WELCOME15',
                'conditions' => ['new_visitor' => true],
                'display_config' => ['position' => 'top-center', 'theme' => 'success']
            ]
        ];
    }
}