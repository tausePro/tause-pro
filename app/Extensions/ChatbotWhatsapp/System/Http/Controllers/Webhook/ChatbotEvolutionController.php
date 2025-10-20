<?php

namespace App\Extensions\ChatbotWhatsapp\System\Http\Controllers\Webhook;

use App\Extensions\Chatbot\System\Models\ChatbotChannelWebhook;
use App\Extensions\ChatbotWhatsapp\System\Services\Evolution\EvolutionConversationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotEvolutionController extends Controller
{
    public function __construct(
        public EvolutionConversationService $service
    ) {}

    public function handle(
        int $chatbotId,
        int $channelId,
        Request $request
    ) {
        // Log para debugging
        Log::info('Evolution Webhook received', [
            'chatbot_id' => $chatbotId,
            'channel_id' => $channelId,
            'event' => $request->get('event'),
            'instance' => $request->get('instance')
        ]);

        // Verificar que sea un mensaje nuevo
        $event = $request->get('event');
        if ($event !== 'messages.upsert') {
            Log::info('Evolution Webhook: Ignored event', ['event' => $event]);
            return response()->json(['status' => 'ignored', 'reason' => 'not_a_message']);
        }

        // Verificar que no sea mensaje enviado por nosotros
        $fromMe = data_get($request->all(), 'data.key.fromMe', false);
        if ($fromMe) {
            Log::info('Evolution Webhook: Ignored message from me');
            return response()->json(['status' => 'ignored', 'reason' => 'message_from_me']);
        }

        // Guardar webhook en BD para debugging
        try {
            ChatbotChannelWebhook::query()->create([
                'chatbot_id' => $chatbotId,
                'chatbot_channel_id' => $channelId,
                'payload' => $request->all(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Evolution Webhook: Could not save webhook', ['error' => $e->getMessage()]);
        }

        // Procesar mensaje
        try {
            $this->service
                ->setIpAddress()
                ->setChatbotId($chatbotId)
                ->setChannelId($channelId)
                ->setPayload($request->all());

            $conversation = $this->service->storeConversation();
            $chatbot = $this->service->getChatbot();

            // Extraer mensaje
            $messageBody = data_get($request->all(), 'data.message.conversation')
                        ?? data_get($request->all(), 'data.message.extendedTextMessage.text')
                        ?? '';

            if ($messageBody) {
                $this->service->insertMessage(
                    conversation: $conversation,
                    message: $messageBody,
                    role: 'user',
                    model: $chatbot->getAttribute('ai_model')
                );
            }

            $this->service->handleWhatsapp();

            Log::info('Evolution Webhook: Message processed successfully', [
                'conversation_id' => $conversation->id
            ]);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Evolution Webhook: Error processing message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}


