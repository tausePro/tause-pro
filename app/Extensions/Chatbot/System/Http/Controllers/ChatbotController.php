<?php

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Extensions\Chatbot\System\Http\Requests\ChatbotCustomizeRequest;
use App\Extensions\Chatbot\System\Http\Requests\ChatbotStoreRequest;
use App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotConversationResource;
use App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotResource;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotTrigger;
use App\Extensions\Chatbot\System\Services\ChatbotService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    public function __construct(public ChatbotService $service) {}

    public function index(Request $request): View
    {
        if (method_exists(Helper::class, 'appIsDemoForChatbot')) {
            if (Helper::appIsDemoForChatbot()) {
                $this->clearDemoData();
            }
        }

        $externalChatbots = $request->user()->externalChatbots->pluck('id')->toArray();
        $unreadAgentMessagesCount = $this->service->unreadAgentMessagesCount($externalChatbots);
        $unreadAiBotMessagesCount = $this->service->unreadAiBotMessagesCount($externalChatbots);
        $allMessagesCount = $this->service->allMessagesCount($externalChatbots);

        return view('chatbot::index', [
            'chatbots' => $this->service->query()
                ->with('channels:id,chatbot_id,channel')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate(perPage: 100),
            'avatars'                  => $this->service->avatars(),
            'unreadAgentMessagesCount' => $unreadAgentMessagesCount,
            'unreadAiBotMessagesCount' => $unreadAiBotMessagesCount,
            'allMessagesCount'         => $allMessagesCount,
        ]);
    }

    public function store(ChatbotStoreRequest $request): JsonResponse|ChatbotResource
    {
        $chatbot = $this->service->query()->create($request->validated());

        return ChatbotResource::make($chatbot);
    }

    public function update(ChatbotCustomizeRequest $request): JsonResponse|ChatbotResource
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $data = $request->validated();

        if ($request->file('header_bg_image_blob')) {
            $path = $request->file('header_bg_image_blob')->store('chatbot', 'public');

            $data['header_bg_image'] = '/uploads/' . $path;
        }

        $chatbot = $this->service->query()->findOrFail($data['id']);

        if ($chatbot->getAttribute('is_demo')) {
            return response()->json([
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $chatbot = $this->service->update($data['id'], $data);

        return ChatbotResource::make($chatbot);
    }

    public function conversations(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $chatbots = $request->user()->externalChatbots->pluck('id')->toArray();

        $conversations = $this->service->conversations($chatbots);

        return ChatbotConversationResource::collection($conversations);
    }

    public function conversationsWithPaginate(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $chatbots = $request->user()->externalChatbots->pluck('id')->toArray();

        $conversations = $this->service->conversationsWithPaginate($chatbots);

        return ChatbotConversationResource::collection($conversations);
    }

    public function searchConversation(Request $request)
    {
        $chatbots = $request->user()->externalChatbots->pluck('id')->toArray();

        $conversations = $this->service->agentConversationsBySearch($chatbots, $request->search ?? '');

        return ChatbotConversationResource::collection($conversations);
    }

    public function delete(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $request->validate(['id' => 'required']);

        $chatbot = $this->service->query()->findOrFail($request->get('id'));

        if ($chatbot->getAttribute('is_demo')) {
            return response()->json([
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        if ($chatbot->getAttribute('user_id') === Auth::id()) {
            $chatbot->delete();
        } else {
            abort(403);
        }

        return response()->json([
            'message' => 'Chatbot deleted successfully',
            'type'    => 'success',
            'status'  => 200,
        ]);
    }

    public function clearDemoData(): void
    {
        Chatbot::query()->where('is_demo', '=', 0)
            ->where('created_at', '<', now()->subMinutes(30))
            ->delete();
    }

    /**
     * Get triggers for a chatbot
     */
    public function getTriggers(string $chatbotId): JsonResponse
    {
        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $triggers = ChatbotTrigger::where('chatbot_id', $chatbot->id)
            ->orderBy('priority', 'asc')
            ->get()
            ->map(function ($trigger) {
                return [
                    'type' => $trigger->trigger_type,
                    'name' => $trigger->name,
                    'message' => $trigger->message_template,
                    'enabled' => $trigger->is_active,
                    'cooldown' => $trigger->cooldown_minutes,
                    'priority' => $trigger->priority,
                    'category' => 'universal',
                    'description' => $this->getTriggerDescription($trigger->trigger_type),
                ];
            });

        return response()->json([
            'triggers' => $triggers
        ]);
    }

    /**
     * Save triggers for a chatbot
     */
    public function saveTriggers(Request $request, string $chatbotId): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $chatbot = Chatbot::where('id', $chatbotId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $triggersData = $request->input('triggers', []);

        foreach ($triggersData as $triggerData) {
            ChatbotTrigger::updateOrCreate(
                [
                    'chatbot_id' => $chatbot->id,
                    'trigger_type' => $triggerData['type']
                ],
                [
                    'name' => $this->getTriggerName($triggerData['type']),
                    'message_template' => $triggerData['message'],
                    'is_active' => $triggerData['enabled'],
                    'action' => 'show_message',
                    'priority' => $triggerData['priority'] ?? 3,
                    'cooldown_minutes' => $triggerData['cooldown'] ?? 5,
                    'frequency_limit' => 3,
                    'conditions' => [],
                    'display_config' => ['position' => 'bottom-right', 'animation' => 'slide-up'],
                ]
            );
        }

        return response()->json([
            'message' => 'Triggers saved successfully',
            'type' => 'success'
        ]);
    }

    /**
     * Get trigger name
     */
    private function getTriggerName(string $type): string
    {
        $names = [
            'welcome_30s' => 'Welcome After 30 Seconds',
            'exit_intent' => 'Exit Intent Offer',
            'page_dwell_2min' => 'Long Page Dwell (2min)',
            'first_visitor_discount' => 'First Visitor Welcome',
            'cart_abandonment' => 'Cart Abandonment',
            'wellness_consultation' => 'Wellness Consultation',
        ];

        return $names[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Get trigger description
     */
    private function getTriggerDescription(string $type): string
    {
        $descriptions = [
            'welcome_30s' => 'Greet visitors who stay on the site for 30 seconds',
            'exit_intent' => 'Show offer when user attempts to leave',
            'page_dwell_2min' => 'Offer help after 2 minutes on the same page',
            'first_visitor_discount' => 'Welcome message for first-time visitors',
            'cart_abandonment' => 'Recover abandoned carts',
            'wellness_consultation' => 'Offer personalized wellness consultation',
        ];

        return $descriptions[$type] ?? 'Proactive trigger';
    }
}
