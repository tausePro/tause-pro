<?php

namespace App\Extensions\Chatbot\System\Http\Controllers\Api;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotCustomer;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Services\GeneratorService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotWhatsappConnectionController extends Controller
{
    public function __construct(
        public GeneratorService $service
    ) {}

    /**
     * Genera un enlace único para conectar WhatsApp
     */
    public function generateConnectionLink(Request $request, Chatbot $chatbot, string $sessionId): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|integer|exists:ext_chatbot_conversations,id',
            'whatsapp_number' => 'required|string|min:10|max:20'
        ]);

        $conversation = ChatbotConversation::findOrFail($request->get('conversation_id'));
        
        // Verificar que la conversación pertenece al chatbot y sesión
        if ($conversation->chatbot_id !== $chatbot->id || $conversation->session_id !== $sessionId) {
            return response()->json(['error' => 'Invalid conversation'], 400);
        }

        // Generar token único
        $connectionToken = Str::random(32);
        
        // Guardar en caché por 10 minutos
        Cache::put("whatsapp_connection_{$connectionToken}", [
            'chatbot_id' => $chatbot->id,
            'conversation_id' => $conversation->id,
            'session_id' => $sessionId,
            'whatsapp_number' => $request->get('whatsapp_number'),
            'created_at' => now()
        ], 600);

        // Obtener número de WhatsApp de soporte desde configuración
        $supportWhatsappNumber = setting('whatsapp_support_number', '+1234567890');
        
        return response()->json([
            'success' => true,
            'connection_token' => $connectionToken,
            'support_whatsapp_number' => $supportWhatsappNumber,
            'connection_url' => route('chatbot.whatsapp.connect', ['token' => $connectionToken]),
            'message' => "Envía '/conectar {$connectionToken}' a {$supportWhatsappNumber} para completar la conexión"
        ]);
    }

    /**
     * Procesa el comando /conectar desde WhatsApp
     */
    public function processWhatsappCommand(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string',
            'body' => 'required|string'
        ]);

        $from = $request->get('from');
        $body = trim($request->get('body'));

        // Verificar si es el comando /conectar
        if (strpos($body, '/conectar') === 0) {
            $parts = explode(' ', $body);
            $token = $parts[1] ?? null;

            if (!$token) {
                return $this->sendWhatsappResponse($from, "❌ Token de conexión no válido. Por favor, usa el enlace generado en el chatbot.");
            }

            // Buscar el token en caché
            $connectionData = Cache::get("whatsapp_connection_{$token}");
            
            if (!$connectionData) {
                return $this->sendWhatsappResponse($from, "❌ Token de conexión expirado o inválido. Por favor, genera uno nuevo desde el chatbot.");
            }

            // Verificar que el número coincida
            $cleanFrom = $this->cleanPhoneNumber($from);
            $cleanStored = $this->cleanPhoneNumber($connectionData['whatsapp_number']);
            
            if ($cleanFrom !== $cleanStored) {
                return $this->sendWhatsappResponse($from, "❌ El número de WhatsApp no coincide con el registrado. Por favor, usa el número correcto.");
            }

            // Conectar la conversación
            $conversation = ChatbotConversation::findOrFail($connectionData['conversation_id']);
            $conversation->update([
                'connect_agent_at' => now(),
                'whatsapp_number' => $cleanFrom
            ]);

            // Limpiar el token
            Cache::forget("whatsapp_connection_{$token}");

            // Enviar mensaje de confirmación
            $this->sendWhatsappResponse($from, "✅ ¡Conexión exitosa! Tu WhatsApp ha sido vinculado al chatbot. Ahora puedes recibir notificaciones aquí.");

            // Agregar mensaje a la conversación
            $this->insertMessage(
                conversation: $conversation,
                message: "WhatsApp conectado exitosamente desde {$cleanFrom}",
                role: 'system',
                model: 'gpt-3.5-turbo'
            );

            return response()->json(['success' => true, 'message' => 'WhatsApp connected successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Command not recognized']);
    }

    /**
     * Verifica el estado de la conexión
     */
    public function checkConnectionStatus(string $token): JsonResponse
    {
        $connectionData = Cache::get("whatsapp_connection_{$token}");
        
        if (!$connectionData) {
            return response()->json(['connected' => false, 'message' => 'Token not found or expired']);
        }

        // Verificar si la conversación ya está conectada
        $conversation = ChatbotConversation::find($connectionData['conversation_id']);
        
        if ($conversation && $conversation->connect_agent_at) {
            return response()->json(['connected' => true, 'message' => 'WhatsApp connected successfully']);
        }

        return response()->json(['connected' => false, 'message' => 'Still waiting for connection']);
    }

    /**
     * Página de conexión (para mostrar instrucciones)
     */
    public function showConnectionPage(string $token)
    {
        $connectionData = Cache::get("whatsapp_connection_{$token}");
        
        if (!$connectionData) {
            return view('chatbot::whatsapp-connection-expired');
        }

        $supportNumber = setting('whatsapp_support_number', '+1234567890');
        
        return view('chatbot::whatsapp-connection', [
            'token' => $token,
            'supportNumber' => $supportNumber,
            'whatsappUrl' => "https://wa.me/" . preg_replace('/[^0-9]/', '', $supportNumber) . "?text=" . urlencode("/conectar {$token}")
        ]);
    }

    /**
     * Envía respuesta a WhatsApp (esto se integraría con tu servicio de WhatsApp)
     */
    private function sendWhatsappResponse(string $to, string $message): JsonResponse
    {
        // Aquí integrarías con tu servicio de WhatsApp (Twilio, etc.)
        // Por ahora solo logueamos
        Log::info("WhatsApp Response to {$to}: {$message}");
        
        return response()->json(['success' => true, 'message' => 'Response queued']);
    }

    /**
     * Limpia el número de teléfono
     */
    private function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Inserta mensaje en la conversación
     */
    private function insertMessage(ChatbotConversation $conversation, string $message, string $role, string $model): ChatbotHistory
    {
        return ChatbotHistory::create([
            'chatbot_id' => $conversation->chatbot_id,
            'conversation_id' => $conversation->id,
            'role' => $role,
            'model' => $model,
            'message' => $message,
            'created_at' => now(),
            'read_at' => now(),
        ]);
    }
}
