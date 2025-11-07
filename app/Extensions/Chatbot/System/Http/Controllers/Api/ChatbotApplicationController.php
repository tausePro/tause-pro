<?php

namespace App\Extensions\Chatbot\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Enums\InteractionType;
use App\Extensions\Chatbot\System\Http\Requests\ChatbotHistoryStoreRequest;
use App\Extensions\Chatbot\System\Http\Resources\Api\ChatbotConversationResource;
use App\Extensions\Chatbot\System\Http\Resources\Api\ChatbotHistoryResource;
use App\Extensions\Chatbot\System\Http\Resources\Api\ChatbotResource;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotCustomer;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Models\ChatbotKnowledgeBaseArticle;
use App\Extensions\Chatbot\System\Services\GeneratorService;
use App\Extensions\ChatbotAgent\System\Services\ChatbotForPanelEventAbly;
use App\Helpers\Classes\Helper;
use App\Helpers\Classes\MarketplaceHelper;
use App\Helpers\Classes\RateLimiter\RateLimiter;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatbotApplicationController extends Controller
{
    public Setting $setting;

    public function __construct(
        public GeneratorService $service
    ) {
        $this->setting = Setting::getCache();
    }

    public function index(Chatbot $chatbot): ChatbotResource
    {
        return ChatbotResource::make($chatbot);
    }

    public function enableSound(Chatbot $chatbot, string $sessionId): JsonResponse
	{
        $customer = ChatbotCustomer::query()
            ->where('session_id', $sessionId)
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->firstOrFail();

        $customer->update([
            'enabled_sound' => ! $customer->getAttribute('enabled_sound'),
        ]);

        return response()->json([
            'enabled_sound' => $customer->getAttribute('enabled_sound'),
        ]);
    }

    public function articles(Request $request, Chatbot $chatbot)
    {
        return ChatbotKnowledgeBaseArticle::query()
            ->whereRaw('JSON_CONTAINS(chatbots, ?)', ['"' . $chatbot->getKey() . '"'])
            ->select(columns: [
                'id',
                'title',
                'description as excerpt',
                'is_featured',
                DB::raw('"#" as link'),
            ])
            ->when($request->get('search'), function ($query, $search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            })->get();
    }

    public function showArticles(Request $request, Chatbot $chatbot, $id)
    {
        return ChatbotKnowledgeBaseArticle::query()
            ->whereRaw('JSON_CONTAINS(chatbots, ?)', ['"' . $chatbot->getKey() . '"'])
            ->select(columns: [
                'id',
                'title',
                'description as excerpt',
                'content',
                'is_featured',
                DB::raw('"#" as link'),
            ])
            ->where('id', $id)
            ->get();
    }

    public function storeFile(
        Request $request,
        Chatbot $chatbot,
        string $sessionId,
        $conversationId = null
    ): ChatbotHistoryResource {
        $request->validate([
            'message'         => 'sometimes|nullable|string',
            'media'           => 'required|mimes:' . setting('media_allowed_types', 'jpg,png,gif,webp,svg,mp4,avi,mov,wmv,flv,webm,mp3,wav,m4a,pdf,doc,docx,xls,xlsx') . '|max:20480',
        ]);

        $chatbotConversation = ChatbotConversation::query()
            ->findOrFail($conversationId);

        $mediaUrl = null;
        $mediaName = null;

        if ($request->hasFile('media')) {
            $mediaName = $request->file('media')->getClientOriginalName();
            $mediaUrl = '/uploads/' . $request->file('media')->store('chatbot-media', 'public');
        }

        $message = $this->insertMessage(
            conversation: $chatbotConversation,
            message: $request['message'] ?: '',
            role: 'user',
            model: $chatbot->getAttribute('ai_model'),
            forcePanelEvent: (bool) $chatbotConversation->getAttribute('connect_agent_at'),
            mediaUrl: $mediaUrl,
            mediaName: $mediaName,
        );

        return ChatbotHistoryResource::make($message)->additional([
            'collect_email' => false,
        ]);
    }

    public function sendEmail(Chatbot $chatbot, string $sessionId, Request $request): ChatbotConversationResource
    {
        $request->validate([
            'email'   => 'required|email',
            'message' => 'required|string',
        ]);

        $customer = ChatbotCustomer::query()->where('session_id', $sessionId)
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->firstOrFail();

        $customer->update([
            'email' => $request->get('email'),
        ]);

        $chatbotConversation = ChatbotConversation::query()
            ->create([
                'chatbot_channel' 			      => 'frame',
                'is_showed_on_history'     => false,
                'country_code'             => Helper::getRequestCountryCode(),
                'ip_address'               => Helper::getRequestIp(),
                'conversation_name'        => 'Anonymous User',
                'chatbot_id'               => $chatbot->getAttribute('id'),
                'session_id'               => $sessionId,
                'chatbot_customer_id'      => $customer?->getKey(),
                'connect_agent_at'         => now(),
                'last_activity_at'         => now(),
                'send_email_at'            => now(),
            ]);

        $history = $this->insertMessage(
            conversation: $chatbotConversation,
            message: 'Customer email: ' . $request->get('email') . "\n\n" . $request->get('message'),
            role: 'user',
            model: $chatbot->getAttribute('ai_model'),
            forcePanelEvent: (bool) $chatbotConversation->getAttribute('connect_agent_at')
        );

        $this->insertMessage(
            conversation: $chatbotConversation,
            message: trans('Your message has been received, and you will get a response shortly.'),
            role: 'assistant',
            model: $chatbot->getAttribute('ai_model'),
            forcePanelEvent: (bool) $chatbotConversation->getAttribute('connect_agent_at')
        );

        try {
            ChatbotForPanelEventAbly::dispatch($chatbot, $chatbotConversation, $history);
        } catch (Exception $e) {
        }

        return ChatbotConversationResource::make($chatbotConversation);

    }

    public function collectEmail(Chatbot $chatbot, string $sessionId, Request $request): JsonResponse
    {
        $request->validate([
            'email'   => 'required|email',
        ]);

        $customer = ChatbotCustomer::query()->where('session_id', $sessionId)
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->firstOrFail();

        $customer->update([
            'email' => $request->get('email'),
        ]);

        return response()->json([
            'message' => 'Email collected successfully.',
            'email'   => $customer->email,
        ]);

    }

    public function indexSession(Chatbot $chatbot, string $sessionId): ChatbotResource
    {
        $conversations = ChatbotConversation::query()
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->where('session_id', $sessionId)
            ->with('lastMessage')
            ->get();

        return ChatbotResource::make($chatbot)->additional([
            'conversations' => ChatbotConversationResource::collection($conversations),
        ]);
    }

    public function connectSupport(Request $request, Chatbot $chatbot, string $sessionId)
    {
        if (MarketplaceHelper::isRegistered('chatbot-agent')) {
            $request->validate(['conversation_id' => 'required|integer|exists:ext_chatbot_conversations,id']);

            /** @var ChatbotConversation $conversation */
            $conversation = ChatbotConversation::find($request->get('conversation_id'));

            if ($chatbot->getAttribute('interaction_type') === InteractionType::SMART_SWITCH) {
                $conversation->update(['connect_agent_at' => now()]);

                $chatbotHistory = null;

                if ($chatbot->getAttribute('connect_message')) {
                    $chatbotHistory = $this->insertMessage(
                        conversation: $conversation,
                        message: trans($chatbot->getAttribute('connect_message')),
                        role: 'assistant',
                        model: $chatbot->getAttribute('ai_model'),
                        forcePanelEvent: true
                    );
                }

                try {
                    ChatbotForPanelEventAbly::dispatch($chatbot, $conversation, $chatbotHistory);
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }

                return ChatbotConversationResource::make($conversation)->additional([
                    'history' => $chatbotHistory ? ChatbotHistoryResource::make($chatbotHistory) : null,
                ]);
            }

            abort(404);
        }
    }

    public function conversionStore(Chatbot $chatbot, string $sessionId): ChatbotConversationResource
    {
        $customer = ChatbotCustomer::query()->where('session_id', $sessionId)
            ->where('chatbot_id', $chatbot->getAttribute('id'))
            ->first();

        $chatbotConversation = ChatbotConversation::query()
            ->create([
                'conversation_name'    => $customer->name ?: 'Anonymous User',
                'chatbot_channel'      => 'frame',
                'is_showed_on_history' => false,
                'ip_address'           => Helper::getRequestIp(),
                'country_code'         => Helper::getRequestCountryCode(),
                'chatbot_id'           => $chatbot->getAttribute('id'),
                'session_id'           => $sessionId,
                'chatbot_customer_id'  => $customer?->getKey(),
                'connect_agent_at'     => $chatbot->getAttribute('interaction_type') === InteractionType::HUMAN_SUPPORT ? now() : null,
                'last_activity_at'     => now(),
            ]);

        $this->insertMessage(
            conversation: $chatbotConversation,
            message: $chatbot->getAttribute('welcome_message'),
            role: 'assistant',
            model: $chatbot->getAttribute('ai_model'),
            forcePanelEvent: (bool) $chatbotConversation->getAttribute('connect_agent_at')
        );

        return ChatbotConversationResource::make($chatbotConversation);
    }

    public function conversion(Chatbot $chatbot, string $sessionId, ChatbotConversation $chatbotConversation): ChatbotConversationResource
    {
        if ($chatbotConversation->getAttribute('chatbot_id') !== $chatbot->getAttribute('id')) {
            abort(404);
        }

        if ($chatbotConversation->getAttribute('session_id') !== $sessionId) {
            abort(404);
        }

        return ChatbotConversationResource::make($chatbotConversation);
    }

    public function export(Chatbot $chatbot, string $sessionId, ChatbotConversation $chatbotConversation)
    {
        $messages = ChatbotHistory::query()
            ->where('conversation_id', $chatbotConversation->getAttribute('id'))
            ->orderBy('id')
            ->get();

        $content = '';

        foreach ($messages as $message) {
            $role = strtoupper($message->role); // örn: user / bot
            $content .= "[{$role}] " . $message->message . PHP_EOL . PHP_EOL;
        }

        $fileName = "conversation-{$chatbotConversation->id}.txt";

        return response()->make($content, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function messages(Chatbot $chatbot, string $sessionId, ChatbotConversation $chatbotConversation): AnonymousResourceCollection
    {
        if ($chatbotConversation->getAttribute('chatbot_id') !== $chatbot->getAttribute('id')) {
            abort(404);
        }

        if ($chatbotConversation->getAttribute('session_id') !== $sessionId) {
            abort(404);
        }

        $messages = ChatbotHistory::query()
            ->where('conversation_id', $chatbotConversation->getAttribute('id'))
            ->orderByDesc('id')
            ->paginate(perPage: request('per_page', 10));

        return ChatbotHistoryResource::collection($messages);
    }

    public function storeMessage(ChatbotHistoryStoreRequest $request, Chatbot $chatbot, string $sessionId, ChatbotConversation $chatbotConversation): ChatbotHistoryResource
    {
        if ($chatbotConversation->getAttribute('chatbot_id') !== $chatbot->getAttribute('id')) {
            abort(404);
        }

        if ($chatbotConversation->getAttribute('session_id') !== $sessionId) {
            abort(404);
        }

        $mediaUrl = null;
        $mediaName = null;

        if ($request->hasFile('media')) {
            $mediaName = $request->file('media')->getClientOriginalName();
            $mediaUrl = '/uploads/' . $request->file('media')->store('chatbot-media', 'public');
        }

        $userMessage = $this->insertMessage(
            conversation: $chatbotConversation,
            message: $request->validated('prompt'),
            role: 'user',
            model: $chatbot->getAttribute('ai_model'),
            forcePanelEvent: false,
            mediaUrl: $mediaUrl,
            mediaName: $mediaName,
        );

        if (! $chatbotConversation->getAttribute('is_showed_on_history')) {
            $chatbotConversation->update(['is_showed_on_history' => true]);
        }

        if ($chatbotConversation->getAttribute('connect_agent_at')) {
            return ChatbotHistoryResource::make($userMessage)->additional([
                'connection'    => 'panel',
                'collect_email' => false,
                'needs_human'   => false,
            ]);
        }

        $clientIp = Helper::getRequestIp();
        $rateLimiter = new RateLimiter('chatbot-extension', 100);

        if (Helper::appIsDemo() && ! $rateLimiter->attempt($clientIp)) {
            $response = 'This feature is disabled in the demo version. You have reached the maximum request limit for today.';
        } else {
            $response = $this->service
                ->setChatbot($chatbot)
                ->setConversation($chatbotConversation)
                ->setPrompt(
                    $request->validated('prompt')
                )
                ->generate();

            if (empty($response)) {
                $response = trans('Sorry, I can\'t answer right now.');
            }
        }

        $needsHuman = false;
        $needsHumanDirect = false;

        $originalResponse = $response;

        $messageToUser = $response;

        if ($chatbot->getAttribute('interaction_type') === InteractionType::SMART_SWITCH) {
            $needsHumanDirect = (bool) preg_match('/\s*\[human-agent-direct\]\s*$/u', $response);

            $response = $needsHumanDirect
                ? preg_replace('/\s*\[human-agent\]\s*$/u', '', $response)
                : $response;

            $response = rtrim($response);

            $needsHuman = (bool) preg_match('/\s*\[human-agent\]\s*$/u', $response);
            $messageToUser = $needsHuman
                ? preg_replace('/\s*\[human-agent\]\s*$/u', '', $response)
                : $response;
            $messageToUser = rtrim($messageToUser);

            if ($needsHumanDirect) {
                $messageToUser = trans('Connecting you to a human agent…');
            }

            if ($needsHuman) {
                $needsHumanDirect = false;
                $messageToUser = trans('Sorry, I’m not able to help with this. Let me connect you to a human agent.');
            }
        }

        $message = $this->insertMessage(
            conversation: $chatbotConversation,
            message: $messageToUser,
            role: 'assistant',
            model: $chatbot->getAttribute('ai_model'),
            forcePanelEvent: false
        );

        $customer = ! $chatbotConversation?->getAttribute('customer')?->getAttribute('email');

        $collectEmail = ChatbotHistory::query()
            ->where('conversation_id', $chatbotConversation->getAttribute('id'))
            ->where('role', '!=', 'user')
            ->count() === 2 && $customer;

        return ChatbotHistoryResource::make($message)->additional([
            'connection'                          => 'ai',
            'collect_email'                       => $collectEmail && $chatbot->getAttribute('is_email_collect'),
            'needs_human'                         => $needsHuman,
            'needs_human_direct'                  => $needsHumanDirect,
            'original_response'                   => $originalResponse,
        ]);
    }

    protected function insertMessage(
        ChatbotConversation $conversation,
        ?string $message,
        string $role,
        string $model,
        bool $forcePanelEvent = false,
        ?string $mediaUrl = null,
        ?string $mediaName = null
    ) {
        $chatbot = $conversation->getAttribute('chatbot');

        $chatbotHistory = ChatbotHistory::query()->create([
            'chatbot_id'      => $conversation->getAttribute('chatbot_id'),
            'conversation_id' => $conversation->getAttribute('id'),
            'role'            => $role,
            'model'           => $this->setting->openai_default_model ?: $model,
            'message'         => $message,
            'created_at'      => now(),
            'read_at'         => $conversation->getAttribute('connect_agent_at') ? null : now(),
            'media_url'       => $mediaUrl,
            'media_name'      => $mediaName,
        ]);

        $sendEvent = $conversation->getAttribute('connect_agent_at') && $chatbot->getAttribute('interaction_type') !== InteractionType::AUTOMATIC_RESPONSE && $role === 'user';

        if ($sendEvent || $forcePanelEvent) {
            $conversation->touch();
            if (MarketplaceHelper::isRegistered('chatbot-agent')) {
                ChatbotForPanelEventAbly::dispatch(
                    $chatbot,
                    $conversation->load('lastMessage'),
                    $chatbotHistory
                );
            }
        }

        return $chatbotHistory;
    }
}
