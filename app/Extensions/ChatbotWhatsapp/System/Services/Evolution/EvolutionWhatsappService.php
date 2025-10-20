<?php

namespace App\Extensions\ChatbotWhatsapp\System\Services\Evolution;

use App\Extensions\Chatbot\System\Models\ChatbotChannel;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionWhatsappService
{
    public ChatbotChannel $chatbotChannel;
    
    protected string $apiUrl;
    protected string $apiKey;
    protected string $instance;

    public function sendText(string $message, string $receiver): array
    {
        try {
            $this->loadConfig();
            
            // Limpiar número (Evolution no usa prefijo "whatsapp:")
            $receiver = $this->cleanPhoneNumber($receiver);
            
            Log::info('Evolution API: Sending text message', [
                'instance' => $this->instance,
                'receiver' => $receiver,
                'message_length' => strlen($message)
            ]);
            
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->apiUrl}/message/sendText/{$this->instance}", [
                'number' => $receiver,
                'text' => $message
            ]);

            if ($response->successful()) {
                Log::info('Evolution API: Message sent successfully', [
                    'response' => $response->json()
                ]);
                
                return [
                    'properties' => $response->json(),
                    'message' => trans('Message sent'),
                    'status' => true
                ];
            }

            throw new Exception('Evolution API Error: ' . $response->body());

        } catch (Exception $exception) {
            Log::error('Evolution API Error: ' . $exception->getMessage(), [
                'exception' => $exception,
                'receiver' => $receiver ?? 'unknown'
            ]);
            
            return [
                'message' => $exception->getMessage(),
                'status' => false
            ];
        }
    }

    public function sendMedia(string $media, string $receiver, string $caption = '', string $mediaType = 'image'): array
    {
        try {
            $this->loadConfig();
            
            $receiver = $this->cleanPhoneNumber($receiver);
            
            Log::info('Evolution API: Sending media message', [
                'instance' => $this->instance,
                'receiver' => $receiver,
                'media_type' => $mediaType
            ]);
            
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->apiUrl}/message/sendMedia/{$this->instance}", [
                'number' => $receiver,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing'
                ],
                'mediaMessage' => [
                    'mediatype' => $mediaType, // 'image', 'video', 'audio', 'document'
                    'media' => $media, // URL or base64
                    'caption' => $caption
                ]
            ]);

            if ($response->successful()) {
                Log::info('Evolution API: Media sent successfully');
                
                return [
                    'properties' => $response->json(),
                    'message' => trans('Media sent'),
                    'status' => true
                ];
            }

            throw new Exception('Evolution API Media Error: ' . $response->body());

        } catch (Exception $exception) {
            Log::error('Evolution API Media Error: ' . $exception->getMessage());
            
            return [
                'message' => $exception->getMessage(),
                'status' => false
            ];
        }
    }

    protected function loadConfig(): void
    {
        $credentials = $this->chatbotChannel['credentials'];
        
        $this->apiUrl = data_get($credentials, 'evolution_api_url');
        $this->apiKey = data_get($credentials, 'evolution_api_key');
        $this->instance = data_get($credentials, 'evolution_instance');
        
        if (!$this->apiUrl || !$this->apiKey || !$this->instance) {
            throw new Exception('Evolution API credentials not configured');
        }
        
        // Remover barra final de URL si existe
        $this->apiUrl = rtrim($this->apiUrl, '/');
    }

    protected function cleanPhoneNumber(string $phone): string
    {
        // Remover "whatsapp:" si existe
        $phone = str_replace('whatsapp:', '', $phone);
        
        // Remover caracteres no numéricos excepto +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Asegurar que no empiece con + (Evolution no lo requiere)
        $phone = ltrim($phone, '+');
        
        return $phone;
    }

    public function setChatbotChannel(ChatbotChannel $chatbotChannel): self
    {
        $this->chatbotChannel = $chatbotChannel;
        return $this;
    }

    public function getChatbotChannel(): ChatbotChannel
    {
        return $this->chatbotChannel;
    }
}


