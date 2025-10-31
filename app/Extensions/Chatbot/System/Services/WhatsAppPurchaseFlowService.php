<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para manejar el flujo de compra conversacional en WhatsApp
 * 
 * Este servicio maneja estados de conversación y recolección de datos
 * paso a paso para crear órdenes desde WhatsApp
 */
class WhatsAppPurchaseFlowService
{
    // Estados del flujo de compra
    const STATE_IDLE = 'idle';
    const STATE_SELECTING_PRODUCT = 'selecting_product';
    const STATE_ASKING_QUANTITY = 'asking_quantity';
    const STATE_ASKING_FIRST_NAME = 'asking_first_name';
    const STATE_ASKING_LAST_NAME = 'asking_last_name';
    const STATE_ASKING_EMAIL = 'asking_email';
    const STATE_ASKING_DEPARTMENT = 'asking_department';
    const STATE_ASKING_CITY = 'asking_city';
    const STATE_ASKING_ADDRESS = 'asking_address';
    const STATE_CREATING_ORDER = 'creating_order';
    const STATE_COMPLETED = 'completed';

    protected WooCommerceService $wooCommerceService;
    protected WompiService $wompiService;
    protected ProductOrchestratorService $productService;

    public function __construct(
        WooCommerceService $wooCommerceService,
        WompiService $wompiService,
        ProductOrchestratorService $productService
    ) {
        $this->wooCommerceService = $wooCommerceService;
        $this->wompiService = $wompiService;
        $this->productService = $productService;
    }

    /**
     * Obtener el estado actual de la conversación
     */
    public function getState(ChatbotConversation $conversation): string
    {
        $cacheKey = "whatsapp_purchase_state_{$conversation->id}";
        return Cache::get($cacheKey, self::STATE_IDLE);
    }

    /**
     * Establecer el estado de la conversación
     */
    public function setState(ChatbotConversation $conversation, string $state): void
    {
        $cacheKey = "whatsapp_purchase_state_{$conversation->id}";
        Cache::put($cacheKey, $state, now()->addHours(24));
        
        Log::info('WhatsApp Purchase Flow: State changed', [
            'conversation_id' => $conversation->id,
            'new_state' => $state
        ]);
    }

    /**
     * Obtener datos temporales de la compra
     */
    public function getPurchaseData(ChatbotConversation $conversation): array
    {
        $cacheKey = "whatsapp_purchase_data_{$conversation->id}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Guardar datos temporales de la compra
     */
    public function setPurchaseData(ChatbotConversation $conversation, array $data): void
    {
        $cacheKey = "whatsapp_purchase_data_{$conversation->id}";
        Cache::put($cacheKey, $data, now()->addHours(24));
    }

    /**
     * Verificar si está en flujo de compra activo
     */
    public function isInPurchaseFlow(ChatbotConversation $conversation): bool
    {
        $state = $this->getState($conversation);
        return $state !== self::STATE_IDLE && $state !== self::STATE_COMPLETED;
    }

    /**
     * Iniciar flujo de compra con un producto
     */
    public function startPurchaseFlow(
        ChatbotConversation $conversation,
        int $productId,
        Chatbot $chatbot
    ): string {
        // Obtener producto
        $product = ChatbotProduct::where('chatbot_id', $chatbot->id)
            ->where('product_id', $productId)
            ->first();

        if (!$product) {
            return "❌ Lo siento, no encontré ese producto.";
        }

        // Guardar producto seleccionado
        $this->setPurchaseData($conversation, [
            'product_id' => $productId,
            'product_name' => $product->name,
            'product_price' => $product->price,
        ]);

        // Cambiar estado
        $this->setState($conversation, self::STATE_ASKING_QUANTITY);

        return "✅ ¡Perfecto! *{$product->name}*\n"
             . "💰 Precio: \${$product->formatted_price}\n\n"
             . "¿Cuántas unidades necesitas?";
    }

    /**
     * Procesar respuesta del usuario según el estado actual
     */
    public function processUserResponse(
        ChatbotConversation $conversation,
        string $message,
        Chatbot $chatbot,
        string $phoneNumber
    ): string {
        $state = $this->getState($conversation);
        $data = $this->getPurchaseData($conversation);

        Log::info('WhatsApp Purchase Flow: Processing response', [
            'conversation_id' => $conversation->id,
            'state' => $state,
            'message' => substr($message, 0, 50)
        ]);

        switch ($state) {
            case self::STATE_ASKING_QUANTITY:
                return $this->handleQuantityResponse($conversation, $message, $data);

            case self::STATE_ASKING_FIRST_NAME:
                return $this->handleFirstNameResponse($conversation, $message, $data);

            case self::STATE_ASKING_LAST_NAME:
                return $this->handleLastNameResponse($conversation, $message, $data);

            case self::STATE_ASKING_EMAIL:
                return $this->handleEmailResponse($conversation, $message, $data);

            case self::STATE_ASKING_DEPARTMENT:
                return $this->handleDepartmentResponse($conversation, $message, $data);

            case self::STATE_ASKING_CITY:
                return $this->handleCityResponse($conversation, $message, $data);

            case self::STATE_ASKING_ADDRESS:
                return $this->handleAddressResponse($conversation, $message, $data, $chatbot, $phoneNumber);

            default:
                return "❌ Error en el flujo de compra. Por favor, intenta nuevamente.";
        }
    }

    /**
     * Manejar respuesta de cantidad
     */
    protected function handleQuantityResponse(
        ChatbotConversation $conversation,
        string $message,
        array $data
    ): string {
        $quantity = (int) trim($message);

        if ($quantity < 1 || $quantity > 100) {
            return "❌ Por favor ingresa una cantidad válida (entre 1 y 100).";
        }

        $data['quantity'] = $quantity;
        $total = $data['product_price'] * $quantity;
        $data['total'] = $total;

        $this->setPurchaseData($conversation, $data);
        $this->setState($conversation, self::STATE_ASKING_FIRST_NAME);

        return "✅ Perfecto, *{$quantity}* " . ($quantity === 1 ? 'unidad' : 'unidades') . "\n"
             . "💰 Subtotal: $" . number_format($total, 0, ',', '.') . " COP\n\n"
             . "_(El costo de envío se coordinará por WhatsApp)_\n\n"
             . "¿Cuál es tu nombre? _(solo el primer nombre)_";
    }

    /**
     * Manejar respuesta de nombre
     */
    protected function handleFirstNameResponse(
        ChatbotConversation $conversation,
        string $message,
        array $data
    ): string {
        $firstName = trim($message);

        if (strlen($firstName) < 2) {
            return "❌ Por favor ingresa un nombre válido.";
        }

        $data['first_name'] = $firstName;
        $this->setPurchaseData($conversation, $data);
        $this->setState($conversation, self::STATE_ASKING_LAST_NAME);

        return "Gracias *{$firstName}* 👋\n\n¿Cuál es tu apellido?";
    }

    /**
     * Manejar respuesta de apellido
     */
    protected function handleLastNameResponse(
        ChatbotConversation $conversation,
        string $message,
        array $data
    ): string {
        $lastName = trim($message);

        if (strlen($lastName) < 2) {
            return "❌ Por favor ingresa un apellido válido.";
        }

        $data['last_name'] = $lastName;
        $this->setPurchaseData($conversation, $data);
        $this->setState($conversation, self::STATE_ASKING_EMAIL);

        return "Perfecto, *{$data['first_name']} {$lastName}* ✅\n\n"
             . "¿Cuál es tu correo electrónico?";
    }

    /**
     * Manejar respuesta de email
     */
    protected function handleEmailResponse(
        ChatbotConversation $conversation,
        string $message,
        array $data
    ): string {
        $email = trim($message);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "❌ Por favor ingresa un correo electrónico válido.\n\n"
                 . "Ejemplo: nombre@ejemplo.com";
        }

        $data['email'] = $email;
        $this->setPurchaseData($conversation, $data);
        $this->setState($conversation, self::STATE_ASKING_DEPARTMENT);

        return "✅ Email registrado: {$email}\n\n"
             . "¿En qué departamento vives?\n\n"
             . "Ejemplo: _Cundinamarca, Antioquia, Valle del Cauca..._";
    }

    /**
     * Manejar respuesta de departamento
     */
    protected function handleDepartmentResponse(
        ChatbotConversation $conversation,
        string $message,
        array $data
    ): string {
        $department = trim($message);

        if (strlen($department) < 3) {
            return "❌ Por favor ingresa un departamento válido.";
        }

        $data['state'] = $department;
        $this->setPurchaseData($conversation, $data);
        $this->setState($conversation, self::STATE_ASKING_CITY);

        return "✅ Departamento: *{$department}*\n\n"
             . "¿En qué ciudad?";
    }

    /**
     * Manejar respuesta de ciudad
     */
    protected function handleCityResponse(
        ChatbotConversation $conversation,
        string $message,
        array $data
    ): string {
        $city = trim($message);

        if (strlen($city) < 3) {
            return "❌ Por favor ingresa una ciudad válida.";
        }

        $data['city'] = $city;
        $this->setPurchaseData($conversation, $data);
        $this->setState($conversation, self::STATE_ASKING_ADDRESS);

        return "✅ Ciudad: *{$city}*\n\n"
             . "Por último, ¿cuál es tu dirección completa?\n\n"
             . "Ejemplo: _Calle 123 #45-67, Apto 301_";
    }

    /**
     * Manejar respuesta de dirección y crear orden
     */
    protected function handleAddressResponse(
        ChatbotConversation $conversation,
        string $message,
        array $data,
        Chatbot $chatbot,
        string $phoneNumber
    ): string {
        $address = trim($message);

        if (strlen($address) < 10) {
            return "❌ Por favor ingresa una dirección completa y válida.";
        }

        $data['address'] = $address;
        $data['phone'] = $phoneNumber;
        $data['address_type'] = 'home'; // Por defecto

        $this->setPurchaseData($conversation, $data);
        $this->setState($conversation, self::STATE_CREATING_ORDER);

        // Crear orden
        try {
            $orderResult = $this->createOrder($chatbot, $data);
            
            if ($orderResult['success']) {
                $this->setState($conversation, self::STATE_COMPLETED);
                
                // Limpiar datos después de 1 hora
                Cache::forget("whatsapp_purchase_data_{$conversation->id}");
                Cache::forget("whatsapp_purchase_state_{$conversation->id}");
                
                return $orderResult['message'];
            } else {
                $this->setState($conversation, self::STATE_IDLE);
                return "❌ " . $orderResult['message'];
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp Purchase Flow: Order creation failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);
            
            $this->setState($conversation, self::STATE_IDLE);
            return "❌ Hubo un error al crear tu orden. Por favor intenta nuevamente más tarde.";
        }
    }

    /**
     * Crear orden en WooCommerce y generar link de pago
     */
    protected function createOrder(Chatbot $chatbot, array $data): array
    {
        // Crear orden en WooCommerce
        $orderData = [
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'address_type' => $data['address_type'],
        ];

        $orderResult = $this->wooCommerceService->createOrder($chatbot, $orderData);

        if (!$orderResult['success']) {
            return [
                'success' => false,
                'message' => 'No pude crear la orden en el sistema. Por favor intenta nuevamente.'
            ];
        }

        // Generar link de pago Wompi
        $paymentData = [
            'amount' => $data['total'],
            'currency' => 'COP',
            'customer_email' => $data['email'],
            'reference' => 'ORDER-' . $orderResult['order_id'],
            'customer_data' => [
                'phone_number' => $data['phone'],
                'full_name' => $data['first_name'] . ' ' . $data['last_name'],
            ],
        ];

        $paymentResult = $this->wompiService->generatePaymentLink($chatbot, $paymentData);

        if (!$paymentResult['success']) {
            return [
                'success' => false,
                'message' => 'Orden creada pero no pude generar el link de pago. Contacta a soporte.'
            ];
        }

        // Construir mensaje de éxito
        $message = "✅ *¡Orden Creada Exitosamente!*\n\n"
                 . "📦 *Detalles del Pedido:*\n"
                 . "━━━━━━━━━━━━━━━━━━━━\n"
                 . "🔢 Orden: #{$orderResult['order_number']}\n"
                 . "📦 Producto: {$data['product_name']}\n"
                 . "📊 Cantidad: {$data['quantity']}\n"
                 . "💰 Total: $" . number_format($data['total'], 0, ',', '.') . " COP\n\n"
                 . "📍 *Envío a:*\n"
                 . "👤 {$data['first_name']} {$data['last_name']}\n"
                 . "📞 {$data['phone']}\n"
                 . "📧 {$data['email']}\n"
                 . "🏠 {$data['address']}\n"
                 . "🌆 {$data['city']}, {$data['state']}\n\n"
                 . "💳 *Paga de forma segura aquí:*\n"
                 . "{$paymentResult['payment_link']}\n\n"
                 . "⏰ _Link válido por 24 horas_\n"
                 . "📦 _El costo de envío se coordinará por WhatsApp_\n\n"
                 . "¡Gracias por tu compra! 🎉";

        return [
            'success' => true,
            'message' => $message,
            'order_id' => $orderResult['order_id'],
            'payment_link' => $paymentResult['payment_link']
        ];
    }

    /**
     * Cancelar flujo de compra
     */
    public function cancelPurchaseFlow(ChatbotConversation $conversation): string
    {
        Cache::forget("whatsapp_purchase_data_{$conversation->id}");
        Cache::forget("whatsapp_purchase_state_{$conversation->id}");
        
        return "❌ Compra cancelada. Si deseas comprar algo, solo dímelo. 😊";
    }

    /**
     * Obtener resumen del estado actual
     */
    public function getStateSummary(ChatbotConversation $conversation): string
    {
        $state = $this->getState($conversation);
        $data = $this->getPurchaseData($conversation);

        if ($state === self::STATE_IDLE) {
            return "No hay compra en proceso.";
        }

        $summary = "📋 *Resumen de tu compra:*\n\n";

        if (isset($data['product_name'])) {
            $summary .= "📦 Producto: {$data['product_name']}\n";
        }
        if (isset($data['quantity'])) {
            $summary .= "📊 Cantidad: {$data['quantity']}\n";
        }
        if (isset($data['total'])) {
            $summary .= "💰 Total: $" . number_format($data['total'], 0, ',', '.') . " COP\n";
        }
        if (isset($data['first_name'])) {
            $summary .= "👤 Nombre: {$data['first_name']}";
            if (isset($data['last_name'])) {
                $summary .= " {$data['last_name']}";
            }
            $summary .= "\n";
        }

        $summary .= "\n_Continúa respondiendo para completar tu compra_";

        return $summary;
    }
}
