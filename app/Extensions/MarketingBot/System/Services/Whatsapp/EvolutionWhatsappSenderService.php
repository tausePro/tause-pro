<?php

namespace App\Extensions\MarketingBot\System\Services\Whatsapp;

use App\Extensions\MarketingBot\System\Enums\CampaignStatus;
use App\Extensions\MarketingBot\System\Models\MarketingConversation;
use App\Extensions\MarketingBot\System\Models\MarketingMessageHistory;
use App\Extensions\MarketingBot\System\Models\Whatsapp\ContactList;
use App\Extensions\MarketingBot\System\Models\Whatsapp\WhatsappChannel;
use App\Extensions\MarketingBot\System\Services\Common\Traits\HasMarketingCampaign;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EvolutionWhatsappSenderService
{
    use HasMarketingCampaign;

    public ?WhatsappChannel $whatsappChannel = null;

    protected string $apiUrl;
    protected string $apiKey;
    protected string $instance;

    public function setWhatsappChannel($id): self
    {
        $this->whatsappChannel = WhatsappChannel::query()
            ->where('user_id', $id)
            ->first();

        return $this;
    }

    public function send(): void
    {
        $marketingCampaign = $this->getMarketingCampaign()->refresh();

        if ($marketingCampaign->status === CampaignStatus::running) {
            return;
        }

        $marketingCampaign->update(['status' => CampaignStatus::running->value]);

        $this->setWhatsappChannel($marketingCampaign->getAttribute('user_id'));

        if (!$this->whatsappChannel) {
            throw new Exception('Whatsapp channel not found for user ID: ' . $marketingCampaign->getAttribute('user_id'));
        }

        // Cargar configuración de Evolution API
        $this->loadEvolutionConfig();

        $contacts = $marketingCampaign->getAttribute('contacts');
        $segments = $marketingCampaign->getAttribute('segments');

        $contactList = ContactList::query()
            ->whereHas('contacts', function ($query) use ($contacts) {
                $query->whereIn('contact_id', $contacts);
            })
            ->when($segments, function ($query) use ($segments) {
                $query->whereHas('segments', function ($query) use ($segments) {
                    $query->whereIn('segment_id', $segments);
                });
            })
            ->select('phone')
            ->get();

        if ($contactList->count()) {
            foreach ($contactList as $contact) {
                $this->sendMessageToContact($contact, $marketingCampaign->getAttribute('content'), $marketingCampaign);
                
                // Pequeña pausa para no saturar la API
                usleep(500000); // 0.5 segundos
            }
        }

        $marketingCampaign->update(['status' => CampaignStatus::published]);
    }

    public function sendMessageToContact(ContactList $contactList, string $content, $marketingCampaign): void
    {
        try {
            $this->sendText($contactList->phone, $content, $marketingCampaign->getAttribute('image') ?: null);

            $conversation = $this->updateOrCreateMarketingConversation($contactList->phone);

            MarketingMessageHistory::query()->create([
                'conversation_id' => $conversation->getKey(),
                'message_id'      => random_int(100000000, 999999999),
                'model'           => null,
                'role'            => 'assistant',
                'message'         => $content,
                'media_url'       => $marketingCampaign->getAttribute('image'),
                'type'            => 'default',
                'message_type'    => 'text',
                'content_type'    => 'text',
                'created_at'      => now(),
            ]);

            Log::info('Evolution Marketing: Message sent', [
                'phone' => $contactList->phone,
                'campaign_id' => $marketingCampaign->id
            ]);

        } catch (Exception $exception) {
            Log::error('Evolution Marketing: Error sending message', [
                'phone' => $contactList->phone,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function updateOrCreateMarketingConversation($phone): Model|Builder
    {
        return MarketingConversation::query()
            ->firstOrCreate([
                'user_id'             => $this->whatsappChannel->getAttribute('user_id'),
                'type'                => 'whatsapp',
                'session_id'          => $this->cleanPhoneNumber($phone),
                'whatsapp_channel_id' => $this->whatsappChannel->getKey(),
            ], [
                'conversation_name' => $phone,
                'customer_payload'  => [
                    'phone' => $phone,
                    'provider' => 'evolution',
                ],
            ]);
    }

    public function sendText(string $receiver, string $message, ?string $mediaUrl = null): array
    {
        try {
            $receiver = $this->cleanPhoneNumber($receiver);

            Log::info('Evolution Marketing: Sending message', [
                'instance' => $this->instance,
                'receiver' => $receiver,
                'has_media' => !empty($mediaUrl)
            ]);

            // Si hay imagen, enviar con media
            if ($mediaUrl) {
                return $this->sendMedia($receiver, $message, $mediaUrl);
            }

            // Enviar solo texto
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->apiUrl}/message/sendText/{$this->instance}", [
                'number' => $receiver,
                'text' => $message,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing'
                ]
            ]);

            if ($response->successful()) {
                Log::info('Evolution Marketing: Message sent successfully', [
                    'response' => $response->json()
                ]);
                
                return [
                    'properties' => $response->json(),
                    'message' => trans('Message sent'),
                    'status' => true
                ];
            }

            Log::error('Evolution Marketing: API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception('Evolution API Error: ' . $response->body());

        } catch (Exception $exception) {
            Log::error('Evolution Marketing: Error', [
                'error' => $exception->getMessage()
            ]);
            
            return [
                'message' => $exception->getMessage(),
                'status' => false
            ];
        }
    }

    public function sendMedia(string $receiver, string $caption, string $mediaUrl): array
    {
        try {
            $fullMediaUrl = url($mediaUrl);
            
            Log::info('Evolution Marketing: Preparing media message', [
                'receiver' => $receiver,
                'media_url' => $fullMediaUrl
            ]);
            
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->apiUrl}/message/sendMedia/{$this->instance}", [
                'number' => $receiver,
                'mediatype' => 'image',
                'media' => $fullMediaUrl,
                'caption' => $caption,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing'
                ]
            ]);

            if ($response->successful()) {
                Log::info('Evolution Marketing: Media sent successfully', [
                    'response' => $response->json()
                ]);
                
                return [
                    'properties' => $response->json(),
                    'message' => trans('Media sent'),
                    'status' => true
                ];
            }

            Log::error('Evolution Marketing: Media API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception('Evolution API Media Error: ' . $response->body());

        } catch (Exception $exception) {
            Log::error('Evolution Marketing: Media Error', [
                'error' => $exception->getMessage()
            ]);
            
            return [
                'message' => $exception->getMessage(),
                'status' => false
            ];
        }
    }

    protected function loadEvolutionConfig(): void
    {
        $credentials = $this->whatsappChannel->getAttribute('evolution_credentials') ?? [];
        
        $this->apiUrl = data_get($credentials, 'api_url');
        $this->apiKey = data_get($credentials, 'api_key');
        $this->instance = data_get($credentials, 'instance');
        
        if (!$this->apiUrl || !$this->apiKey || !$this->instance) {
            throw new Exception('Evolution API credentials not configured for this channel');
        }
        
        // Remover barra final de URL si existe
        $this->apiUrl = rtrim($this->apiUrl, '/');
    }

    protected function cleanPhoneNumber(string $phone): string
    {
        // Remover espacios, guiones y caracteres especiales
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);
        
        // Remover "whatsapp:" si existe
        $phone = str_replace('whatsapp:', '', $phone);
        
        // Remover + si existe
        $phone = ltrim($phone, '+');
        
        return $phone;
    }
}
