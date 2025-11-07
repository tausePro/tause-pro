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
use App\Extensions\Chatbot\System\Services\ChatbotService;
use App\Extensions\ChatbotAgent\System\Services\ChatbotForFrameEventAbly;
use App\Extensions\ChatbotTelegram\System\Services\Telegram\TelegramService;
use App\Extensions\ChatbotWhatsapp\System\Services\Twillio\TwilioWhatsappService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
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
                        app(TwilioWhatsappService::class)
                            ->setChatbotChannel($chatbotChannel)
                            ->sendText(
                                $request['message'],
                                $chatbotConversation->getAttribute('customer_channel_id')
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
}
