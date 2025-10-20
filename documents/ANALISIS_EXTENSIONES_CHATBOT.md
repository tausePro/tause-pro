# 📊 ANÁLISIS COMPLETO: EXTENSIONES CHATBOT Y MIGRACIÓN EVOLUTION API

**Fecha:** 3 de Octubre, 2025  
**Proyecto:** Tause Pro 9.4  
**Objetivo:** Activar extensiones críticas y migrar de Twilio a Evolution API

---

## 🎯 RESUMEN EJECUTIVO

### Estado Actual
- **Total extensiones:** 74
- **Instaladas:** 27 (36%)
- **Licenciadas sin instalar:** 20
- **No licenciadas:** 27

### Extensiones Críticas Identificadas

| Extensión | Estado | Prioridad | Notas |
|-----------|--------|-----------|-------|
| **ChatbotAgent** (Human Agent) | ❌ No instalada | 🔴 ALTA | Sistema de agentes humanos |
| **ChatbotWhatsapp** | ❌ No instalada | 🔴 ALTA | Usa Twilio → Migrar a Evolution API |
| **ChatbotMessenger** | ❌ No instalada | 🟡 MEDIA | Integración Facebook Messenger |
| **ChatbotVoice** | ❌ No instalada | 🟢 BAJA | Chat por voz |
| **ChatbotTelegram** | ❌ No instalada | 🟢 BAJA | Ya existe MarketingBot Telegram |

---

## 🔍 ANÁLISIS DETALLADO

### 1. CHATBOT AGENT (HUMAN AGENT)

#### Funcionalidad
Sistema que permite transferir conversaciones del chatbot AI a agentes humanos reales.

#### Características Principales
```
📌 3 Modos de Operación:
   1. SMART_SWITCH: AI + Human Agent (automático según condiciones)
   2. AUTOMATIC_RESPONSE: Solo AI
   3. HUMAN_SUPPORT: Solo Human Agent

📌 Condiciones de Transferencia:
   - Cuando el problema es demasiado complejo o ambiguo
   - Cuando el cliente está frustrado o insatisfecho
   - Temas sensibles (legal, financiero, médico)
   - IA falla después de múltiples intentos
   - Se requiere empatía o inteligencia emocional
   - Solicitud fuera del alcance de la IA
   - Cliente solicita explícitamente un humano

📌 Comando de Activación:
   Usuario escribe: #humanagent
```

#### Integración
- ✅ Se integra con WhatsApp (Twilio)
- ✅ Se integra con Telegram
- ✅ Se integra con Messenger
- ✅ Se integra con el chat web (iframe)
- ✅ Usa Ably para comunicación en tiempo real

#### Archivos Clave
```
app/Extensions/ChatbotAgent/
├── System/
│   ├── ChatbotAgentServiceProvider.php
│   ├── Http/Controllers/ChatbotAgentController.php
│   ├── Events/ChatbotForPanelEvent.php
│   └── Services/
│       ├── ChatbotForPanelEventAbly.php
│       └── ChatbotForFrameEventAbly.php
└── resources/views/
    ├── index.blade.php (Panel de agentes)
    └── particles/chatbot-config.blade.php
```

---

### 2. CHATBOT WHATSAPP

#### Funcionalidad
Permite al chatbot responder mensajes de WhatsApp a través de la API de Twilio.

#### Arquitectura Actual (TWILIO)

```
┌─────────────────────────────────────────────────────┐
│                    FLUJO ACTUAL                     │
└─────────────────────────────────────────────────────┘

Usuario WhatsApp
       ↓
    Twilio
       ↓
Webhook: /chatbot/{id}/channel/{channelId}/whatsapp
       ↓
ChatbotTwilioController
       ↓
TwilioConversationService
       ↓
  - Detecta comando #humanagent
  - Genera respuesta con AI
  - Guarda conversación
       ↓
TwilioWhatsappService
       ↓
   Twilio API
       ↓
Usuario WhatsApp
```

#### Credenciales Twilio Requeridas
```json
{
  "whatsapp_sid": "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "whatsapp_token": "auth_token_here",
  "whatsapp_phone": "+14155238886",
  "whatsapp_sandbox_phone": "+14155238886",
  "whatsapp_environment": "sandbox|production"
}
```

#### Métodos Principales - TwilioWhatsappService

```php
// Enviar mensaje de texto
sendText($message, $receiver)

// Crear cliente Twilio
client(): Client
  - Usa whatsapp_sid (username)
  - Usa whatsapp_token (password)

// Formatear número receptor
receiverCheck($receiver)
  - Agrega prefijo "whatsapp:"
```

#### Archivos Clave
```
app/Extensions/ChatbotWhatsapp/
├── config/
│   └── whatsapp-channel.php
├── System/
│   ├── ChatbotWhatsappServiceProvider.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ChatbotWhatsappController.php
│   │   │   └── Webhook/ChatbotTwilioController.php
│   │   └── Requests/WhatsappChannelStoreRequest.php
│   └── Services/Twillio/
│       ├── TwilioWhatsappService.php
│       └── TwilioConversationService.php
└── resources/views/
    └── channel-card.blade.php
```

---

### 3. CHATBOT MESSENGER

#### Funcionalidad
Integración con Facebook Messenger para responder mensajes automáticamente.

#### Estructura
```
app/Extensions/ChatbotMessenger/
├── config/
│   └── messenger-channel.php
├── System/
│   ├── ChatbotMessengerServiceProvider.php
│   ├── Http/Controllers/
│   ├── Helpers/
│   └── Services/
└── resources/views/
```

#### Estado
- Extensión completa disponible localmente
- Requiere credenciales de Facebook App
- Similar a WhatsApp pero para Messenger

---

## 🔄 MIGRACIÓN: TWILIO → EVOLUTION API

### ¿Qué es Evolution API?

Evolution API es una API de WhatsApp **sin necesidad de Twilio**, se conecta directamente a WhatsApp Web/Business usando conexiones independientes.

#### Ventajas vs Twilio

| Característica | Twilio | Evolution API |
|----------------|--------|---------------|
| **Costo** | Pago por mensaje | Gratis (self-hosted) |
| **Conexión** | Número Twilio | Número propio WhatsApp |
| **Limitaciones** | Plantillas aprobadas | Sin restricciones |
| **Setup** | Requiere cuenta Twilio | Solo servidor |
| **Webhook** | Sí | Sí |

### Arquitectura Propuesta (EVOLUTION API)

```
┌─────────────────────────────────────────────────────┐
│                   FLUJO PROPUESTO                   │
└─────────────────────────────────────────────────────┘

Usuario WhatsApp
       ↓
  Evolution API Instance
  (Servidor propio)
       ↓
Webhook: /chatbot/{id}/channel/{channelId}/whatsapp/evolution
       ↓
ChatbotEvolutionController
       ↓
EvolutionConversationService
       ↓
  - Detecta comando #humanagent
  - Genera respuesta con AI
  - Guarda conversación
       ↓
EvolutionWhatsappService
       ↓
  Evolution API
  (HTTP REST)
       ↓
Usuario WhatsApp
```

### Endpoints Evolution API

```
Base URL: https://evolution-api.yourdomain.com

1. Enviar Mensaje:
POST /message/sendText/{instance}
{
  "number": "5511999999999",
  "options": {
    "delay": 1200,
    "presence": "composing"
  },
  "textMessage": {
    "text": "Mensaje aquí"
  }
}

2. Webhook de Mensajes Recibidos:
Tu endpoint recibe:
{
  "event": "messages.upsert",
  "instance": "instance_name",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0..."
    },
    "message": {
      "conversation": "Hola, necesito ayuda"
    },
    "messageTimestamp": "1234567890"
  }
}
```

### Credenciales Evolution API Requeridas

```json
{
  "evolution_api_url": "https://evolution-api.yourdomain.com",
  "evolution_api_key": "B6D03xxxx-xxxx-xxxx-xxxx-xxxxxxxxxx",
  "evolution_instance": "my_chatbot_instance",
  "whatsapp_phone": "+5511999999999"
}
```

---

## 📋 PLAN DE IMPLEMENTACIÓN

### FASE 1: INSTALACIÓN DE EXTENSIONES (1-2 horas)

#### 1.1 Instalar ChatbotAgent
```bash
# Subir archivos a producción
cd app/Extensions/ChatbotAgent
# Marcar como licensed=true, installed=true
# Ejecutar migraciones
php artisan migrate --path=app/Extensions/ChatbotAgent/database/migrations
# Publicar assets si existen
php artisan vendor:publish --tag=chatbot-agent
```

#### 1.2 Instalar ChatbotWhatsapp
```bash
# Subir archivos a producción
cd app/Extensions/ChatbotWhatsapp
# Marcar como licensed=true, installed=true
# Ejecutar migraciones si existen
# Publicar assets
```

#### 1.3 Instalar ChatbotMessenger
```bash
# Subir archivos a producción
cd app/Extensions/ChatbotMessenger
# Marcar como licensed=true, installed=true
# Publicar assets
```

#### 1.4 Limpiar cachés
```bash
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
systemctl restart php8.3-fpm
```

---

### FASE 2: CREACIÓN DE SERVICIO EVOLUTION API (2-3 horas)

#### 2.1 Crear EvolutionWhatsappService

**Ubicación:** `app/Extensions/ChatbotWhatsapp/System/Services/Evolution/EvolutionWhatsappService.php`

```php
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

    public function __construct()
    {
        // Se configurarán dinámicamente desde $chatbotChannel
    }

    public function sendText(string $message, string $receiver): array
    {
        try {
            $this->loadConfig();
            
            // Limpiar número (Evolution no usa prefijo "whatsapp:")
            $receiver = $this->cleanPhoneNumber($receiver);
            
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->apiUrl}/message/sendText/{$this->instance}", [
                'number' => $receiver,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing'
                ],
                'textMessage' => [
                    'text' => $message
                ]
            ]);

            if ($response->successful()) {
                return [
                    'properties' => $response->json(),
                    'message' => trans('Message sent'),
                    'status' => true
                ];
            }

            throw new Exception($response->body());

        } catch (Exception $exception) {
            Log::error('Evolution API Error: ' . $exception->getMessage());
            
            return [
                'message' => $exception->getMessage(),
                'status' => false
            ];
        }
    }

    public function sendMedia(string $media, string $receiver, string $caption = ''): array
    {
        try {
            $this->loadConfig();
            
            $receiver = $this->cleanPhoneNumber($receiver);
            
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
                    'mediatype' => 'image', // or 'video', 'audio', 'document'
                    'media' => $media, // URL or base64
                    'caption' => $caption
                ]
            ]);

            if ($response->successful()) {
                return [
                    'properties' => $response->json(),
                    'message' => trans('Media sent'),
                    'status' => true
                ];
            }

            throw new Exception($response->body());

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
    }

    protected function cleanPhoneNumber(string $phone): string
    {
        // Remover "whatsapp:" si existe
        $phone = str_replace('whatsapp:', '', $phone);
        
        // Remover caracteres no numéricos excepto +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Asegurar que empiece con + o agregarlo
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
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
```

#### 2.2 Crear EvolutionConversationService

**Ubicación:** `app/Extensions/ChatbotWhatsapp/System/Services/Evolution/EvolutionConversationService.php`

```php
<?php

namespace App\Extensions\ChatbotWhatsapp\System\Services\Evolution;

use App\Extensions\Chatbot\System\Enums\InteractionType;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotChannel;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Services\GeneratorService;
use App\Extensions\ChatbotAgent\System\Services\ChatbotForPanelEventAbly;
use App\Helpers\Classes\MarketplaceHelper;
use Illuminate\Support\Facades\Log;

class EvolutionConversationService
{
    protected ?ChatbotConversation $conversation = null;
    protected ?Chatbot $chatbot = null;
    protected string $humanAgentCommand = 'humanagent';
    protected int $chatbotId;
    protected int $channelId;
    protected ?string $ipAddress = null;
    protected ?array $payload = null;

    public function handleWhatsapp(): void
    {
        $evolutionService = app(EvolutionWhatsappService::class)
            ->setChatbotChannel($this->getChatbotChannel());

        // Parsear datos de Evolution API
        $remoteJid = data_get($this->payload, 'data.key.remoteJid');
        $phoneNumber = $this->extractPhoneFromJid($remoteJid);
        $messageBody = data_get($this->payload, 'data.message.conversation') 
                    ?? data_get($this->payload, 'data.message.extendedTextMessage.text');

        $conversation = $this->conversation;
        $chatbot = $conversation->chatbot;

        // Si ya está conectado a agente humano
        if ($conversation->connect_agent_at) {
            if ($conversation->last_activity_at->diffInMinutes() > 10) {
                $this->closeInactiveConversation($conversation, $evolutionService, $phoneNumber);
                return;
            }
            return;
        }

        $conversation->update(['last_activity_at' => now()]);

        if ($messageBody) {
            $this->processTextMessage($messageBody, $conversation, $chatbot, $evolutionService, $phoneNumber);
        } else {
            $this->sendUnsupportedMessageType($conversation, $chatbot, $evolutionService, $phoneNumber);
        }
    }

    protected function extractPhoneFromJid(string $jid): string
    {
        // Evolution envía: "5511999999999@s.whatsapp.net"
        // Extraer solo el número
        $phone = explode('@', $jid)[0];
        
        // Asegurar formato con +
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }

    protected function processTextMessage(
        string $messageBody,
        ChatbotConversation $conversation,
        Chatbot $chatbot,
        EvolutionWhatsappService $service,
        string $phoneNumber
    ): void {
        if ($this->isHumanAgentCommand($chatbot, $messageBody)) {
            $this->connectToHumanAgent($chatbot, $conversation, $service, $phoneNumber);
            return;
        }

        $response = $this->generateResponse($messageBody) 
                 ?? trans("Sorry, I can't answer right now.");

        if (!$conversation->connect_agent_at 
            && $chatbot->interaction_type === InteractionType::SMART_SWITCH 
            && MarketplaceHelper::isRegistered('chatbot-agent')) {
            $response .= "\n\n\nTo speak with a live support agent, please enter the #{$this->humanAgentCommand} command.";
        }

        $service->sendText($response, $phoneNumber);
        $this->insertMessage($conversation, $response, 'assistant', $chatbot->ai_model);
    }

    protected function connectToHumanAgent(
        Chatbot $chatbot,
        ChatbotConversation $conversation,
        EvolutionWhatsappService $service,
        string $phoneNumber
    ): void {
        $conversation->update(['connect_agent_at' => now()]);

        if ($connectMessage = $chatbot->connect_message) {
            $chatbotHistory = $this->insertMessage($conversation, $connectMessage, 'assistant', $chatbot->ai_model, true);
            $service->sendText($connectMessage, $phoneNumber);
            $this->dispatchAgentEvent($chatbot, $conversation, $chatbotHistory);
        }
    }

    // ... resto de métodos similares a TwilioConversationService
}
```

#### 2.3 Crear Controller para Webhook Evolution

**Ubicación:** `app/Extensions/ChatbotWhatsapp/System/Http/Controllers/Webhook/ChatbotEvolutionController.php`

```php
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
            'payload' => $request->all()
        ]);

        // Verificar que sea un mensaje nuevo
        $event = $request->get('event');
        if ($event !== 'messages.upsert') {
            return ['status' => 'ignored', 'reason' => 'not_a_message'];
        }

        // Verificar que no sea mensaje enviado por nosotros
        $fromMe = data_get($request->all(), 'data.key.fromMe', false);
        if ($fromMe) {
            return ['status' => 'ignored', 'reason' => 'message_from_me'];
        }

        // Guardar webhook en BD para debugging
        ChatbotChannelWebhook::query()->create([
            'chatbot_id' => $chatbotId,
            'chatbot_channel_id' => $channelId,
            'payload' => $request->all(),
            'created_at' => now(),
        ]);

        // Procesar mensaje
        $this->service
            ->setIpAddress()
            ->setChatbotId($chatbotId)
            ->setChannelId($channelId)
            ->setPayload($request->all());

        $conversation = $this->service->storeConversation();
        $chatbot = $this->service->getChatbot();

        // Extraer mensaje
        $messageBody = data_get($request->all(), 'data.message.conversation')
                    ?? data_get($request->all(), 'data.message.extendedTextMessage.text');

        $this->service->insertMessage(
            conversation: $conversation,
            message: $messageBody ?? '',
            role: 'user',
            model: $chatbot->getAttribute('ai_model')
        );

        $this->service->handleWhatsapp();

        return ['status' => 'success'];
    }
}
```

---

### FASE 3: MODIFICAR VISTAS Y CONFIGURACIÓN (1 hora)

#### 3.1 Crear Vista para Canal Evolution

**Ubicación:** `app/Extensions/ChatbotWhatsapp/resources/views/channel-card-evolution.blade.php`

```blade
@php
    $image = 'vendor/chatbot-multi-channel/icons/whatsapp.svg';
@endphp

<x-modal
    class:modal-head="border-b-0"
    class:modal-body="pt-3"
    class:modal-container="max-w-[600px]"
>
    <x-slot:trigger
        class="rounded-sm lqd-social-media-card flex flex-col text-heading-foreground transition-all hover:scale-105"
        variant="outline"
        size="lg"
        type="button"
    >
        <figure class="mb-8 w-9">
            <img src="{{ asset($image) }}" alt="whatsapp-evolution" />
        </figure>
        <h4 class="mb-2 text-lg">
            WhatsApp (Evolution API)
        </h4>
    </x-slot:trigger>

    <x-slot:modal>
        <h3 class="mb-3.5">
            WhatsApp via Evolution API
        </h3>
        <p class="mb-7 text-heading-foreground/60">
            @lang('Connect your WhatsApp using Evolution API (no Twilio needed)')
        </p>

        <form id="storeForm-whatsapp-evolution" action="{{ route('dashboard.chatbot-multi-channel.whatsapp.store') }}">
            <input hidden name="channel" value="whatsapp">
            <input hidden name="provider" value="evolution">
            <input hidden name="user_id" value="{{ Auth::id() }}">
            
            <div class="mb-3">
                <x-forms.input
                    :label="__('Evolution API URL')"
                    name="credentials[evolution_api_url]"
                    placeholder="https://evolution-api.yourdomain.com"
                    size="lg"
                    required
                />
            </div>

            <div class="mb-3">
                <x-forms.input
                    :label="__('API Key')"
                    name="credentials[evolution_api_key]"
                    placeholder="B6D03xxxx-xxxx-xxxx-xxxx-xxxxxxxxxx"
                    size="lg"
                    required
                />
            </div>

            <div class="mb-3">
                <x-forms.input
                    :label="__('Instance Name')"
                    name="credentials[evolution_instance]"
                    placeholder="my_chatbot_instance"
                    size="lg"
                    required
                />
            </div>

            <div class="mb-3">
                <x-forms.input
                    :label="__('WhatsApp Phone Number')"
                    name="credentials[whatsapp_phone]"
                    placeholder="+5511999999999"
                    size="lg"
                    required
                />
            </div>

            <div class="mb-5 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>Webhook URL:</strong><br>
                    <code class="text-xs">{{ url('/') }}/chatbot/{chatbot_id}/channel/{channel_id}/whatsapp/evolution</code>
                </p>
            </div>

            <x-button
                type="button"
                x-on:click="storeChannel('storeForm-whatsapp-evolution')"
                size="lg"
            >
                <span x-show="storeChannelFetch">Loading...</span>
                <span x-show="!storeChannelFetch">{{ __('Add Channel') }}</span>
            </x-button>
        </form>
    </x-slot:modal>
</x-modal>
```

#### 3.2 Modificar ServiceProvider para Registrar Rutas Evolution

**Ubicación:** `app/Extensions/ChatbotWhatsapp/System/ChatbotWhatsappServiceProvider.php`

Agregar ruta para webhook Evolution:

```php
// Ruta existente Twilio
$router->post(
    'chatbot/{chatbotId}/channel/{channelId}/whatsapp',
    [ChatbotTwilioController::class, 'handle']
)->name('chatbot.whatsapp.twilio.webhook');

// NUEVA ruta Evolution
$router->post(
    'chatbot/{chatbotId}/channel/{channelId}/whatsapp/evolution',
    [ChatbotEvolutionController::class, 'handle']
)->name('chatbot.whatsapp.evolution.webhook');
```

---

### FASE 4: MODIFICAR ChatbotAgentController (30 minutos)

Para que Human Agent funcione con Evolution API, modificar:

**Archivo:** `app/Extensions/ChatbotAgent/System/Http/Controllers/ChatbotAgentController.php`

**Línea 233:** Modificar para detectar provider

```php
if ($chatbotChannel?->channel === 'whatsapp') {
    $provider = data_get($chatbotChannel['credentials'], 'provider', 'twilio');
    
    if ($provider === 'evolution') {
        // Usar Evolution API
        app(\App\Extensions\ChatbotWhatsapp\System\Services\Evolution\EvolutionWhatsappService::class)
            ->setChatbotChannel($chatbotChannel)
            ->sendText(
                $request['message'],
                $chatbotConversation->getAttribute('customer_channel_id')
            );
    } else {
        // Usar Twilio (legacy)
        app(TwilioWhatsappService::class)
            ->setChatbotChannel($chatbotChannel)
            ->sendText(
                $request['message'],
                $chatbotConversation->getAttribute('customer_channel_id')
            );
    }
}
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Pre-requisitos
- [ ] Servidor Evolution API configurado y funcionando
- [ ] Instancia de WhatsApp creada en Evolution API
- [ ] API Key de Evolution API generada
- [ ] Webhook URL accesible desde internet (https)

### Instalación Extensiones
- [ ] Subir ChatbotAgent a producción
- [ ] Marcar ChatbotAgent como licensed=true, installed=true
- [ ] Subir ChatbotWhatsapp a producción
- [ ] Marcar ChatbotWhatsapp como licensed=true, installed=true
- [ ] Subir ChatbotMessenger a producción
- [ ] Marcar ChatbotMessenger como licensed=true, installed=true
- [ ] Ejecutar migraciones
- [ ] Limpiar todos los cachés

### Creación Evolution API
- [ ] Crear EvolutionWhatsappService.php
- [ ] Crear EvolutionConversationService.php
- [ ] Crear ChatbotEvolutionController.php
- [ ] Crear vista channel-card-evolution.blade.php
- [ ] Registrar ruta webhook en ServiceProvider
- [ ] Modificar ChatbotAgentController para soportar Evolution

### Testing
- [ ] Crear canal WhatsApp Evolution en dashboard
- [ ] Configurar webhook en Evolution API
- [ ] Enviar mensaje de prueba desde WhatsApp
- [ ] Verificar que chatbot responde
- [ ] Probar comando #humanagent
- [ ] Verificar que agente humano puede responder

---

## 📚 DOCUMENTACIÓN ADICIONAL

### Evolution API Docs
- Instalación: https://doc.evolution-api.com/
- Mensajes: https://doc.evolution-api.com/pt/messages/send-text
- Webhooks: https://doc.evolution-api.com/pt/webhooks/webhook

### Configurar Evolution API en Servidor

```bash
# Docker Compose (recomendado)
git clone https://github.com/EvolutionAPI/evolution-api.git
cd evolution-api
cp .env.example .env
# Editar .env con configuraciones
docker-compose up -d
```

### Configurar Webhook en Evolution

```bash
curl -X POST https://evolution-api.yourdomain.com/webhook/set/my_instance \
  -H "apikey: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://app.tause.pro/chatbot/{chatbot_id}/channel/{channel_id}/whatsapp/evolution",
    "webhook_by_events": true,
    "events": [
      "MESSAGES_UPSERT"
    ]
  }'
```

---

## 🚨 CONSIDERACIONES IMPORTANTES

1. **Compatibilidad Retroactiva:**
   - NO eliminar código de Twilio
   - Mantener ambas opciones disponibles
   - Usar campo `provider` en credentials para elegir

2. **Manejo de Errores:**
   - Evolution API puede desconectarse
   - Implementar reintentos
   - Log detallado de errores

3. **Rate Limiting:**
   - WhatsApp tiene límites de mensajes
   - Evolution API respeta estos límites
   - Implementar cola si es necesario

4. **Seguridad:**
   - Validar webhook signature si Evolution lo soporta
   - No exponer API keys en logs
   - Usar HTTPS obligatorio

5. **Testing:**
   - Probar en ambiente sandbox primero
   - Verificar todos los tipos de mensajes
   - Probar handoff a human agent

---

## 🎯 PRÓXIMOS PASOS

1. ¿Proceder con instalación de extensiones?
2. ¿Crear servicios Evolution API?
3. ¿Configurar servidor Evolution API primero?
4. ¿Activar todas las extensiones licenciadas?

**¿Por dónde quieres empezar?** 🚀


