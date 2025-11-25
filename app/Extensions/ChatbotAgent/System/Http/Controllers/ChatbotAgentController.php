<?php

namespace App\Extensions\ChatbotAgent\System\Http\Controllers;

use App\Extensions\Chatbot\System\Enums\TicketStatusEnum;
use App\Extensions\Chatbot\System\Http\Resources\Admin\ChatbotConversationResource;
use App\Extensions\Chatbot\System\Http\Resources\Api\ChatbotHistoryResource;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotChannel;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotCustomer;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Models\ChatbotLeadHistory;
use App\Extensions\Chatbot\System\Services\ChatbotService;
use App\Extensions\ChatbotAgent\System\Services\ChatbotForFrameEventAbly;
use App\Extensions\ChatbotTelegram\System\Services\Telegram\TelegramService;
use App\Extensions\ChatbotWhatsapp\System\Services\Evolution\EvolutionWhatsappService;
use App\Extensions\ChatbotWhatsapp\System\Services\Twillio\TwilioWhatsappService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class ChatbotAgentController extends Controller
{
    public function __construct(public ChatbotService $service) {}

    public function index(Request $request)
    {
        return view('chatbot-agent::index');
    }

    public function notification(Request $request): JsonResponse
    {
        $chatbots = $request->user()->externalChatbots->pluck('id')->toArray();

        $count = ChatbotConversation::query()
            ->whereHas('histories', function ($query) {
                $query->whereNull('read_at');
            })
            ->whereIn('chatbot_id', $chatbots)
            ->count();

        return response()->json([
            'class'  => 'hidden',
            'count'  => $count,
            'status' => 'success',
        ]);
    }

    public function closed(Request $request): ChatbotConversationResource|JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $request->validate([
            'conversation_id'   => 'required|exists:ext_chatbot_conversations,id',
        ]);

        $conversation = ChatbotConversation::query()->find($request['conversation_id']);

        $conversation->update([
            'ticket_status' => TicketStatusEnum::closed->value,
        ]);

        return ChatbotConversationResource::make($conversation)->additional([
            'message' => 'This feature is disabled in free version.',
            'status'  => 'success',
        ]);
    }

    public function pinned(Request $request): ChatbotConversationResource|JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $request->validate([
            'conversation_id'   => 'required|exists:ext_chatbot_conversations,id',
        ]);

        $conversation = ChatbotConversation::query()->find($request['conversation_id']);

        $maxPinned = ChatbotConversation::query()
            ->max('pinned') ?? 0;

        $currentPinned = $conversation->getAttribute('pinned') ?? 0;

        if ($currentPinned > 0) {
            $newPinnedValue = 0;
        } else {
            $newPinnedValue = $maxPinned + 1;
        }

        $conversation->update([
            'pinned' => $newPinnedValue,
        ]);

        $message = $newPinnedValue > 0
            ? trans('Conversation pinned.')
            : trans('Conversation unpinned.');

        return ChatbotConversationResource::make($conversation)->additional([
            'message' => $message,
            'status'  => 'success',
        ]);
    }

    public function name(Request $request): ChatbotConversationResource|JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $request->validate([
            'conversation_id'   => 'required|exists:ext_chatbot_conversations,id',
            'conversation_name' => 'required|string',
        ]);

        $conversation = ChatbotConversation::query()->find($request['conversation_id']);

        if ($conversation->customer) {
            $conversation->customer?->update([
                'name' => $request['conversation_name'],
            ]);
        }

        $conversation->update(['conversation_name' => $request['conversation_name']]);

        return ChatbotConversationResource::make($conversation);
    }

    public function update(Request $request): ChatbotConversationResource|JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $request->validate([
            'conversation_id'   => 'required|integer|exists:ext_chatbot_conversations,id',
            'conversation_name' => 'sometimes|string',
            'color'             => 'sometimes|string',
        ]);

        $conversation = ChatbotConversation::query()->find($request['conversation_id']);

        $conversation->update($request->only(['conversation_name', 'color']));

        $customer = $this->createCustomer($conversation->chatbot, $conversation->getAttribute('session_id'));

        $customer->update([
            'name' => $request['conversation_name'] ?? $customer->name,
        ]);

        return ChatbotConversationResource::make($conversation);
    }

    private function createCustomer(Chatbot $chatbot, string $session)
    {
        return ChatbotCustomer::query()->firstOrCreate([
            'user_id'         => $chatbot->getAttribute('user_id'),
            'chatbot_id'      => $chatbot->getAttribute('id'),
            'session_id'      => $session,
            'chatbot_channel' => 'frame',
        ]);
    }

    public function store(Request $request): ChatbotHistoryResource|JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $request->validate([
            'conversation_id' => 'required|integer|exists:ext_chatbot_conversations,id',
            'message'         => 'sometimes|nullable|string',
            'media'           => 'sometimes|nullable|mimes:' . setting('media_allowed_types', 'jpg,png,gif,webp,svg,mp4,avi,mov,wmv,flv,webm,mp3,wav,m4a,pdf,doc,docx,xls,xlsx') . '|max:20480',
        ]);

        $mediaUrl = null;
        $mediaName = null;

        if ($request->hasFile('media')) {
            $mediaName = $request->file('media')->getClientOriginalName();
            $mediaUrl = '/uploads/' . $request->file('media')->store('chatbot-media', 'public');
        }

        $chatbotConversation = ChatbotConversation::query()
            ->with('chatbot')
            ->find($request['conversation_id']);

        $history = ChatbotHistory::query()->create([
            'user_id'         => Auth::id(),
            'chatbot_id'      => $chatbotConversation->getAttribute('chatbot_id'),
            'conversation_id' => $chatbotConversation->getAttribute('id'),
            'model'           => $chatbotConversation->chatbot->getAttribute('ai_model'),
            'media_url'       => $mediaUrl,
            'media_name'      => $mediaName,
            'role'            => 'assistant',
            'message'         => $request['message'],
            'created_at'      => now(),
        ]);

        try {
            if ($chatbotConversation->getAttribute('chatbot_channel_id')) {
                /**
                 * @var ChatbotChannel $chatbotChannel
                 */
                $chatbotChannel = $chatbotConversation->getAttribute('chatbotChannel');

                if ($chatbotChannel) {
                    if ($chatbotChannel?->channel === 'whatsapp' && $chatbotConversation->getAttribute('customer_channel_id')) {
                        $this->sendWhatsappMessage(
                            chatChannel: $chatbotChannel,
                            conversation: $chatbotConversation,
                            message: $request['message'],
                            mediaUrl: $mediaUrl,
                            mediaFile: $request->file('media')
                            );
                    }

                    if ($chatbotChannel?->channel === 'telegram') {
                        app(TelegramService::class)
                            ->setChannel($chatbotChannel)
                            ->sendText(
                                $request['message'],
                                $chatbotConversation->getAttribute('customer_channel_id')
                            );
                    }
                }
            } else {
                ChatbotForFrameEventAbly::dispatch($history, $chatbotConversation->sessionId());
            }
        } catch (Exception $e) {
            //
        }

        return ChatbotHistoryResource::make($history)->additional([
            'message' => 'Message was sent.',
            'status'  => 'success',
        ]);
    }

    public function conversations(Request $request): AnonymousResourceCollection
    {
        $chatbots = $request->user()->externalChatbots->pluck('id')->toArray();

        $conversations = $this->service->agentConversations($chatbots, 'updated_at');

        return ChatbotConversationResource::collection($conversations);
    }

    public function conversationsWithPaginate(Request $request): AnonymousResourceCollection
    {
        $chatbots = $request->user()->externalChatbots->pluck('id')->toArray();

        $conversations = $this->service->agentConversationsWithPaginate($chatbots);

        $count = $this->service->agentConversationsWithQuery($chatbots)
            ->selectRaw('ticket_status, count(*) as count')
            ->groupBy('ticket_status')
            ->pluck('count', 'ticket_status');

        $new = $count?->get('new', 0);

        $closed = $count?->get('closed', 0);

        return ChatbotConversationResource::collection($conversations)->additional([
            'status_count' => [
                'all'    => $new + $closed,
                'new'    => $new,
                'closed' => $closed,
            ],
        ]);
    }

    public function conversationsHistorySession(Request $request): AnonymousResourceCollection
    {
        $request->validate(['sessionId' => 'required|string']);

        $conversations = $this->service->historyConversationsWithPaginate(
            sessionId: $request->sessionId
        );

        return ChatbotConversationResource::collection($conversations);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $request->validate(['conversation_id' => 'required|integer|exists:ext_chatbot_conversations,id']);

        ChatbotHistory::query()->where('conversation_id', request('conversation_id'))->update(['read_at' => now()]);

        $conversation = ChatbotConversation::query()->find(request('conversation_id'));

        return ChatbotHistoryResource::collection($conversation->getAttribute('histories'));
    }

    public function searchConversation(Request $request)
    {
        $chatbots = $request->user()->externalChatbots->pluck('id')->toArray();

        $conversations = $this->service->agentConversationsBySearch($chatbots, $request->search ?? '');

        return ChatbotConversationResource::collection($conversations);
    }

    public function destroy(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'type'    => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        try {
            $request->validate(['conversation_id' => 'required|integer|exists:ext_chatbot_conversations,id']);

            ChatbotConversation::query()->find(request('conversation_id'))?->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Successfully removed conversation',
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'status'       => 'error',
                'message'      => 'Something went wrong',
                'errorMessage' => $th->getMessage(),
            ]);
        }

    }

    public function toggleAiHandling(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This feature is disabled in Demo version.',
            ], 403);
        }

        $validated = $request->validate([
            'chatbot_id'          => ['required', 'integer', 'exists:ext_chatbots,id'],
            'ai_handling_enabled' => ['required', 'boolean'],
        ]);

        $chatbot = Chatbot::query()
            ->where('id', $validated['chatbot_id'])
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->firstOrFail();

        $chatbot->update([
            'ai_handling_enabled' => $validated['ai_handling_enabled'],
        ]);

        return response()->json([
            'status'  => 'success',
            'chatbot' => $chatbot->fresh(),
        ]);
    }

    private function sendWhatsappMessage(
        ChatbotChannel $chatbotChannel,
        ChatbotConversation $conversation,
        ?string $message,
        ?string $mediaUrl,
        ?UploadedFile $mediaFile = null
    ): void {
        if (! $message && ! $mediaUrl) {
            return;
        }

        $receiver = $conversation->getAttribute('customer_channel_id');
        $provider = data_get($chatbotChannel->credentials, 'provider', 'twilio');

        if ($provider === 'evolution') {
            $service = app(EvolutionWhatsappService::class)
                ->setChatbotChannel($chatbotChannel);

            if ($mediaUrl) {
                $service->sendMedia(
                    $this->absoluteMediaUrl($mediaUrl),
                    $receiver,
                    $message ?? '',
                    $this->detectEvolutionMediaType($mediaFile)
                );

                return;
            }

            $service->sendText($message ?? '', $receiver);

            return;
        }

        if (! $message) {
            return;
        }

        app(TwilioWhatsappService::class)
            ->setChatbotChannel($chatbotChannel)
            ->sendText(
                $message,
                $receiver
            );
    }

    private function absoluteMediaUrl(?string $mediaUrl): ?string
    {
        if (! $mediaUrl) {
            return null;
        }

        if (Str::startsWith($mediaUrl, ['http://', 'https://'])) {
            return $mediaUrl;
        }

        return url($mediaUrl);
    }

    private function detectEvolutionMediaType(?UploadedFile $mediaFile): string
    {
        if (! $mediaFile) {
            return 'document';
        }

        $mimeType = $mediaFile->getMimeType();

        if ($mimeType && Str::startsWith($mimeType, 'image/')) {
            return 'image';
        }

        if ($mimeType && Str::startsWith($mimeType, 'video/')) {
            return 'video';
        }

        if ($mimeType && Str::startsWith($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    /**
     * Update lead data for a customer
     */
    public function updateLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id'       => 'required|integer|exists:ext_chatbot_customers,id',
            'lead_value'        => 'nullable|numeric|min:0',
            'lead_priority'     => 'nullable|integer|min:0|max:4',
            'negotiation_notes' => 'nullable|string|max:5000',
            'next_action_at'    => 'nullable|date',
            'crm_tags'          => 'nullable|array',
            'crm_tags.*'        => 'string|max:50',
        ]);

        try {
            $customer = ChatbotCustomer::findOrFail($validated['customer_id']);

            // Verify ownership through chatbot
            $chatbot = Chatbot::find($customer->chatbot_id);
            if (! $chatbot || $chatbot->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Track changes for history
            $changes = [];

            // Check lead_value change
            if (isset($validated['lead_value']) && $validated['lead_value'] != $customer->lead_value) {
                $changes[] = [
                    'type'      => ChatbotLeadHistory::EVENT_VALUE_CHANGED,
                    'field'     => 'lead_value',
                    'old_value' => $customer->lead_value,
                    'new_value' => $validated['lead_value'],
                ];
            }

            // Check priority change
            if (isset($validated['lead_priority']) && $validated['lead_priority'] != $customer->lead_priority) {
                $priorityLabels = [0 => 'Not Set', 1 => 'Low', 2 => 'Medium', 3 => 'High', 4 => 'Urgent'];
                $changes[] = [
                    'type'        => ChatbotLeadHistory::EVENT_PRIORITY_CHANGED,
                    'field'       => 'lead_priority',
                    'old_value'   => $customer->lead_priority,
                    'new_value'   => $validated['lead_priority'],
                    'description' => sprintf(
                        'Priority changed from %s to %s',
                        $priorityLabels[$customer->lead_priority ?? 0] ?? 'Unknown',
                        $priorityLabels[$validated['lead_priority']] ?? 'Unknown'
                    ),
                ];
            }

            // Check notes change
            if (isset($validated['negotiation_notes']) && $validated['negotiation_notes'] != $customer->negotiation_notes) {
                $changes[] = [
                    'type'      => ChatbotLeadHistory::EVENT_NOTE_ADDED,
                    'field'     => 'negotiation_notes',
                    'old_value' => $customer->negotiation_notes,
                    'new_value' => $validated['negotiation_notes'],
                ];
            }

            // Check next_action_at change
            if (isset($validated['next_action_at']) && $validated['next_action_at'] != $customer->next_action_at?->format('Y-m-d\TH:i')) {
                $changes[] = [
                    'type'      => ChatbotLeadHistory::EVENT_FOLLOW_UP_SCHEDULED,
                    'field'     => 'next_action_at',
                    'old_value' => $customer->next_action_at?->format('Y-m-d H:i'),
                    'new_value' => $validated['next_action_at'],
                ];
            }

            // Check tags changes
            if (isset($validated['crm_tags'])) {
                $oldTags = $customer->crm_tags ?? [];
                $newTags = $validated['crm_tags'];
                $addedTags = array_diff($newTags, $oldTags);
                $removedTags = array_diff($oldTags, $newTags);

                foreach ($addedTags as $tag) {
                    $changes[] = [
                        'type'      => ChatbotLeadHistory::EVENT_TAG_ADDED,
                        'field'     => 'crm_tags',
                        'new_value' => $tag,
                    ];
                }

                foreach ($removedTags as $tag) {
                    $changes[] = [
                        'type'      => ChatbotLeadHistory::EVENT_TAG_REMOVED,
                        'field'     => 'crm_tags',
                        'old_value' => $tag,
                    ];
                }
            }

            // Save changes to history
            foreach ($changes as $change) {
                ChatbotLeadHistory::log(
                    customerId: $customer->id,
                    eventType: $change['type'],
                    fieldName: $change['field'] ?? null,
                    oldValue: $change['old_value'] ?? null,
                    newValue: $change['new_value'] ?? null,
                    description: $change['description'] ?? null
                );
            }

            // Update customer
            $customer->update([
                'lead_value'        => $validated['lead_value'] ?? $customer->lead_value,
                'lead_priority'     => $validated['lead_priority'] ?? $customer->lead_priority,
                'negotiation_notes' => $validated['negotiation_notes'] ?? $customer->negotiation_notes,
                'next_action_at'    => $validated['next_action_at'] ?? $customer->next_action_at,
                'crm_tags'          => $validated['crm_tags'] ?? $customer->crm_tags,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Lead updated successfully',
                'customer' => $customer->fresh(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getLeadHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:ext_chatbot_customers,id',
        ]);

        try {
            $customer = ChatbotCustomer::findOrFail($validated['customer_id']);

            // Verify ownership through chatbot
            $chatbot = Chatbot::find($customer->chatbot_id);
            if (! $chatbot || $chatbot->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $history = $customer->leadHistory()
                ->with('user:id,name,avatar')
                ->limit(50)
                ->get()
                ->map(function ($item) {
                    return [
                        'id'          => $item->id,
                        'event_type'  => $item->event_type,
                        'event_label' => $item->getEventLabel(),
                        'event_icon'  => $item->getEventIcon(),
                        'event_color' => $item->getEventColor(),
                        'field_name'  => $item->field_name,
                        'old_value'   => $item->old_value,
                        'new_value'   => $item->new_value,
                        'description' => $item->description,
                        'user'        => $item->user ? [
                            'name'   => $item->user->name,
                            'avatar' => $item->user->avatar,
                        ] : null,
                        'created_at'  => $item->created_at->diffForHumans(),
                        'timestamp'   => $item->created_at->format('M d, Y H:i'),
                    ];
                });

            return response()->json([
                'success' => true,
                'history' => $history,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching lead history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dashboard de Leads - Vista principal
     */
    public function leadsDashboard(Request $request)
    {
        $user = Auth::user();
        $chatbotIds = $user->externalChatbots->pluck('id')->toArray();

        // Obtener métricas
        $metrics = $this->getLeadsMetrics($chatbotIds);

        // Obtener chatbots para el filtro
        $chatbots = $user->externalChatbots()->select('id', 'title')->get();

        return view('chatbot-agent::leads.dashboard', [
            'metrics'  => $metrics,
            'chatbots' => $chatbots,
        ]);
    }

    /**
     * API para obtener leads con filtros
     */
    public function getLeads(Request $request): JsonResponse
    {
        $user = Auth::user();
        $chatbotIds = $user->externalChatbots->pluck('id')->toArray();

        $query = ChatbotCustomer::query()
            ->whereIn('chatbot_id', $chatbotIds)
            ->with(['leadHistory' => function ($q) {
                $q->latest()->limit(1);
            }]);

        // Filtro por chatbot
        if ($request->filled('chatbot_id')) {
            $query->where('chatbot_id', $request->chatbot_id);
        }

        // Filtro por prioridad
        if ($request->filled('priority')) {
            $query->where('lead_priority', $request->priority);
        }

        // Filtro por canal
        if ($request->filled('channel')) {
            $query->where('chatbot_channel', $request->channel);
        }

        // Filtro por valor mínimo
        if ($request->filled('min_value')) {
            $query->where('lead_value', '>=', $request->min_value);
        }

        // Filtro por valor máximo
        if ($request->filled('max_value')) {
            $query->where('lead_value', '<=', $request->max_value);
        }

        // Filtro por próxima acción
        if ($request->filled('next_action')) {
            switch ($request->next_action) {
                case 'overdue':
                    $query->where('next_action_at', '<', now());
                    break;
                case 'today':
                    $query->whereDate('next_action_at', today());
                    break;
                case 'week':
                    $query->whereBetween('next_action_at', [now(), now()->addWeek()]);
                    break;
            }
        }

        // Filtro por tags
        if ($request->filled('tag')) {
            $query->whereJsonContains('crm_tags', $request->tag);
        }

        // Búsqueda por nombre, email o teléfono
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortField = $request->get('sort', 'updated_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['name', 'lead_value', 'lead_priority', 'next_action_at', 'updated_at', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        }

        // Paginación
        $perPage = $request->get('per_page', 20);
        $leads = $query->paginate($perPage);

        // Formatear datos
        $leads->getCollection()->transform(function ($lead) {
            return [
                'id'              => $lead->id,
                'name'            => $lead->name ?: 'Anonymous',
                'email'           => $lead->email,
                'phone'           => $lead->phone,
                'avatar'          => $lead->avatar,
                'channel'         => $lead->chatbot_channel,
                'lead_value'      => $lead->lead_value,
                'lead_priority'   => $lead->lead_priority,
                'next_action_at'  => $lead->next_action_at?->format('Y-m-d H:i'),
                'next_action_human' => $lead->next_action_at?->diffForHumans(),
                'is_overdue'      => $lead->next_action_at && $lead->next_action_at->isPast(),
                'tags'            => $lead->crm_tags ?? [],
                'notes'           => $lead->negotiation_notes,
                'last_activity'   => $lead->updated_at->diffForHumans(),
                'created_at'      => $lead->created_at->format('M d, Y'),
                'chatbot_id'      => $lead->chatbot_id,
            ];
        });

        return response()->json([
            'success' => true,
            'leads'   => $leads,
        ]);
    }

    /**
     * Obtener métricas de leads
     */
    protected function getLeadsMetrics(array $chatbotIds): array
    {
        $baseQuery = ChatbotCustomer::whereIn('chatbot_id', $chatbotIds);

        return [
            'total_leads'      => (clone $baseQuery)->count(),
            'total_value'      => (clone $baseQuery)->sum('lead_value') ?? 0,
            'high_priority'    => (clone $baseQuery)->whereIn('lead_priority', [3, 4])->count(),
            'overdue_actions'  => (clone $baseQuery)->where('next_action_at', '<', now())->count(),
            'today_actions'    => (clone $baseQuery)->whereDate('next_action_at', today())->count(),
            'week_actions'     => (clone $baseQuery)->whereBetween('next_action_at', [now(), now()->addWeek()])->count(),
            'by_priority'      => [
                'urgent' => (clone $baseQuery)->where('lead_priority', 4)->count(),
                'high'   => (clone $baseQuery)->where('lead_priority', 3)->count(),
                'medium' => (clone $baseQuery)->where('lead_priority', 2)->count(),
                'low'    => (clone $baseQuery)->where('lead_priority', 1)->count(),
                'none'   => (clone $baseQuery)->where(function ($q) {
                    $q->whereNull('lead_priority')->orWhere('lead_priority', 0);
                })->count(),
            ],
            'by_channel'       => [
                'whatsapp'  => (clone $baseQuery)->where('chatbot_channel', 'whatsapp')->count(),
                'livechat'  => (clone $baseQuery)->where('chatbot_channel', 'frame')->count(),
                'telegram'  => (clone $baseQuery)->where('chatbot_channel', 'telegram')->count(),
                'messenger' => (clone $baseQuery)->where('chatbot_channel', 'messenger')->count(),
            ],
        ];
    }

    /**
     * Exportar leads a CSV
     */
    public function exportLeads(Request $request)
    {
        $user = Auth::user();
        $chatbotIds = $user->externalChatbots->pluck('id')->toArray();

        $leads = ChatbotCustomer::whereIn('chatbot_id', $chatbotIds)
            ->orderBy('lead_priority', 'desc')
            ->orderBy('lead_value', 'desc')
            ->get();

        $filename = 'leads_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, [
                'Nombre', 'Email', 'Teléfono', 'Canal', 'Valor', 'Prioridad',
                'Próxima Acción', 'Tags', 'Notas', 'Creado', 'Actualizado',
            ]);

            $priorityLabels = [0 => 'Sin asignar', 1 => 'Baja', 2 => 'Media', 3 => 'Alta', 4 => 'Urgente'];

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->name ?: 'Anónimo',
                    $lead->email,
                    $lead->phone,
                    $lead->chatbot_channel,
                    $lead->lead_value ?? 0,
                    $priorityLabels[$lead->lead_priority ?? 0],
                    $lead->next_action_at?->format('Y-m-d H:i'),
                    implode(', ', $lead->crm_tags ?? []),
                    $lead->negotiation_notes,
                    $lead->created_at->format('Y-m-d H:i'),
                    $lead->updated_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
