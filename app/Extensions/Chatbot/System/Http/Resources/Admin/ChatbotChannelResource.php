<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Http\Resources\Admin;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use JsonSerializable;

class ChatbotChannelResource extends JsonResource
{
    public function toArray(Request $request): array|Arrayable|JsonSerializable
    {
        return [
            'id'           => $this->id,
            'channel'      => $this->channel,
            'channel_id'   => $this->channelId(),
            'credentials'  => $this->credentials,
            'webhook'      => $this->getWebhook(),
        ];
    }

    private function getWebhook(): ?string
    {
        return match ($this->channel) {
            'whatsapp' => $this->getWhatsappWebhook(),
            'telegram' => Route::has('api.v2.chatbot.channel.telegram.post.handle') ? route('api.v2.chatbot.channel.telegram.post.handle', [
                'chatbotId' => $this->chatbot_id,
                'channelId' => $this->id,
            ]) : null,
            'messenger' => Route::has('api.v2.chatbot.channel.messenger.post.handle') ? route('api.v2.chatbot.channel.messenger.post.handle', [
                'chatbotId' => $this->chatbot_id,
                'channelId' => $this->id,
            ]) : null,
            default    => null,
        };
    }

    private function getWhatsappWebhook(): ?string
    {
        // Detectar si usa Evolution API o Twilio
        $provider = data_get($this->credentials, 'provider', 'twilio');
        
        if ($provider === 'evolution') {
            return Route::has('api.v2.chatbot.channel.evolution.post.handle') 
                ? route('api.v2.chatbot.channel.evolution.post.handle', [
                    'chatbotId' => $this->chatbot_id,
                    'channelId' => $this->id,
                ]) 
                : null;
        }
        
        // Default: Twilio
        return Route::has('api.v2.chatbot.channel.twilio.post.handle') 
            ? route('api.v2.chatbot.channel.twilio.post.handle', [
                'chatbotId' => $this->chatbot_id,
                'channelId' => $this->id,
            ]) 
            : null;
    }

    private function channelId(): ?string
    {
        return match ($this->channel) {
            'whatsapp'  => $this->getWhatsappChannelId(),
            'telegram'  => data_get($this->credentials, 'telegram_bot_name'),
            'messenger' => data_get($this->credentials, 'page_name'),
            default     => null,
        };
    }

    private function getWhatsappChannelId(): ?string
    {
        $provider = data_get($this->credentials, 'provider', 'twilio');
        
        if ($provider === 'evolution') {
            // Para Evolution API, el channel ID es el nombre de la instancia
            return data_get($this->credentials, 'evolution_instance');
        }
        
        // Para Twilio, es el número de teléfono
        return data_get($this->credentials, 'whatsapp_phone');
    }
}
