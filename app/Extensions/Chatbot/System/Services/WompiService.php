<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WompiService
{
    protected const WOMPI_API_URL = 'https://production.wompi.co/v1';

    protected const WOMPI_SANDBOX_URL = 'https://sandbox.wompi.co/v1';

    /**
     * Generar Payment Link para un producto
     */
    public function generatePaymentLink(
        Chatbot $chatbot,
        ChatbotProduct $product,
        array $customerData,
        int $quantity = 1,
        ?int $orderId = null
    ): array {
        try {
            // Validar configuración
            if (! $this->hasValidConfiguration($chatbot)) {
                return [
                    'success' => false,
                    'message' => 'Configuración de Wompi incompleta',
                ];
            }

            // Calcular total
            $amount = $product->price * $quantity * 100; // Wompi usa centavos

            // Generar referencia única (incluir order_id si existe)
            $reference = $orderId
                ? "WC-{$orderId}"
                : $this->generateReference($chatbot, $product);

            // Preparar payload - limpiar HTML de description
            $cleanDescription = $product->description
                ? strip_tags($product->description)
                : $product->name;

            // Wompi tiene límite de caracteres en description
            $description = strlen($cleanDescription) > 100
                ? substr($cleanDescription, 0, 97) . '...'
                : $cleanDescription;

            $payload = [
                'name'             => $product->name,
                'description'      => $description,
                'single_use'       => false,
                'collect_shipping' => false,
                'currency'         => 'COP',
                'amount_in_cents'  => (int) $amount,
                'redirect_url'     => $this->getRedirectUrl($chatbot),
                'expires_at'       => now()->addHours(24)->toIso8601String(),
            ];

            // Agregar customer_data si está disponible
            if (! empty($customerData['phone'])) {
                $payload['customer_data'] = [
                    'phone_number' => (string) $customerData['phone'],
                ];

                if (! empty($customerData['name'])) {
                    $payload['customer_data']['full_name'] = (string) $customerData['name'];
                }
            }

            // Crear Payment Link
            $response = $this->makeRequest('POST', '/payment_links', $payload, $chatbot);

            if (! $response['success']) {
                return $response;
            }

            $data = $response['data'];

            // Construir el payment link desde el ID
            $paymentLinkId = $data['id'] ?? null;
            $paymentLink = $paymentLinkId
                ? "https://checkout.wompi.co/l/{$paymentLinkId}"
                : null;

            Log::info('Wompi payment link created', [
                'payment_link_id' => $paymentLinkId,
                'payment_link'    => $paymentLink,
            ]);

            return [
                'success'         => true,
                'payment_link'    => $paymentLink,
                'payment_link_id' => $paymentLinkId,
                'reference'       => $reference,
                'amount'          => $amount / 100,
                'expires_at'      => $payload['expires_at'],
                'data'            => $data,
            ];

        } catch (Exception $e) {
            Log::error('Wompi payment link error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al generar link de pago: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generar Payment Link para carrito múltiple
     * Alias: generatePaymentLinkForCart
     */
    public function generatePaymentLinkForCart(
        Chatbot $chatbot,
        array $items, // [['product' => ChatbotProduct, 'quantity' => int], ...]
        float $total,
        array $customerData,
        ?int $orderId = null
    ): array {
        return $this->generateCartPaymentLink($chatbot, $items, $customerData, $total, $orderId);
    }

    /**
     * Generar Payment Link para carrito múltiple
     */
    public function generateCartPaymentLink(
        Chatbot $chatbot,
        array $items, // [['product' => ChatbotProduct, 'quantity' => int], ...]
        array $customerData,
        ?float $preCalculatedTotal = null,
        ?int $orderId = null
    ): array {
        try {
            if (! $this->hasValidConfiguration($chatbot)) {
                return [
                    'success' => false,
                    'message' => 'Configuración de Wompi incompleta',
                ];
            }

            // Calcular total y descripción
            $total = 0;
            $description = [];

            foreach ($items as $item) {
                $product = $item['product'] ?? null;
                $quantity = $item['quantity'] ?? 1;

                if ($product) {
                    $subtotal = $product->price * $quantity;
                    $total += $subtotal;
                    $description[] = "{$product->name} x{$quantity}";
                }
            }

            // Usar total pre-calculado si se proporciona
            if ($preCalculatedTotal !== null && $preCalculatedTotal > 0) {
                $total = $preCalculatedTotal;
            }

            $amount = (int) ($total * 100); // Centavos

            // Generar referencia con order_id si existe
            $reference = $orderId
                ? "WC-{$orderId}"
                : $this->generateReference($chatbot);

            // Limitar descripción a 100 caracteres
            $descriptionText = implode(', ', $description);
            if (strlen($descriptionText) > 100) {
                $descriptionText = substr($descriptionText, 0, 97) . '...';
            }

            // Preparar payload
            $payload = [
                'name'             => 'Pedido #' . ($orderId ?? 'N/A') . ' - ' . $chatbot->title,
                'description'      => $descriptionText ?: 'Compra en línea',
                'single_use'       => false,
                'collect_shipping' => false,
                'currency'         => 'COP',
                'amount_in_cents'  => $amount,
                'redirect_url'     => $this->getRedirectUrl($chatbot),
                'expires_at'       => now()->addHours(24)->toIso8601String(),
            ];

            // Agregar customer_data si está disponible
            if (! empty($customerData['phone'])) {
                $payload['customer_data'] = [
                    'phone_number' => (string) $customerData['phone'],
                ];

                if (! empty($customerData['name'])) {
                    $payload['customer_data']['full_name'] = (string) $customerData['name'];
                }
            }

            // Crear Payment Link
            $response = $this->makeRequest('POST', '/payment_links', $payload, $chatbot);

            if (! $response['success']) {
                return $response;
            }

            $data = $response['data'];

            // Construir el payment link desde el ID
            $paymentLinkId = $data['id'] ?? null;
            $paymentLink = $paymentLinkId
                ? "https://checkout.wompi.co/l/{$paymentLinkId}"
                : ($data['permalink'] ?? $data['url'] ?? null);

            Log::info('Wompi cart payment link created', [
                'payment_link_id' => $paymentLinkId,
                'payment_link'    => $paymentLink,
                'order_id'        => $orderId,
                'items_count'     => count($items),
            ]);

            return [
                'success'         => true,
                'payment_link'    => $paymentLink,
                'payment_link_id' => $paymentLinkId,
                'reference'       => $reference,
                'amount'          => $total,
                'items'           => count($items),
                'expires_at'      => $payload['expires_at'],
                'data'            => $data,
            ];

        } catch (Exception $e) {
            Log::error('Wompi cart payment link error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al generar link de pago: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verificar estado de transacción
     */
    public function checkTransactionStatus(string $transactionId, Chatbot $chatbot): array
    {
        try {
            $response = $this->makeRequest('GET', "/transactions/{$transactionId}", [], $chatbot);

            if (! $response['success']) {
                return $response;
            }

            $data = $response['data'];

            return [
                'success'        => true,
                'status'         => $data['status'] ?? 'UNKNOWN',
                'reference'      => $data['reference'] ?? null,
                'amount'         => isset($data['amount_in_cents']) ? $data['amount_in_cents'] / 100 : 0,
                'payment_method' => $data['payment_method_type'] ?? null,
                'data'           => $data,
            ];

        } catch (Exception $e) {
            Log::error('Wompi transaction check error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al verificar transacción: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Realizar request a la API de Wompi
     */
    protected function makeRequest(string $method, string $endpoint, array $data, Chatbot $chatbot): array
    {
        $baseUrl = $chatbot->wompi_environment === 'production'
            ? self::WOMPI_API_URL
            : self::WOMPI_SANDBOX_URL;

        $url = $baseUrl . $endpoint;

        $headers = [
            'Authorization' => 'Bearer ' . $this->getPrivateKey($chatbot),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];

        $request = Http::withHeaders($headers)->timeout(30);

        $response = match (strtoupper($method)) {
            'GET'    => $request->get($url, $data),
            'POST'   => $request->post($url, $data),
            'PUT'    => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            default  => throw new Exception('Invalid HTTP method'),
        };

        if (! $response->successful()) {
            $responseBody = $response->json();

            Log::error('Wompi API error', [
                'status'        => $response->status(),
                'url'           => $url,
                'method'        => $method,
                'sent_data'     => $data,
                'response_body' => $response->body(),
            ]);

            // Extraer mensaje de error más descriptivo
            $errorMessage = 'Error de API Wompi: ' . $response->status();
            if (isset($responseBody['error']['messages'])) {
                $messages = is_array($responseBody['error']['messages'])
                    ? implode(', ', $responseBody['error']['messages'])
                    : $responseBody['error']['messages'];
                $errorMessage .= ' - ' . $messages;
            } elseif (isset($responseBody['error']['reason'])) {
                $reason = is_array($responseBody['error']['reason'])
                    ? json_encode($responseBody['error']['reason'])
                    : $responseBody['error']['reason'];
                $errorMessage .= ' - ' . $reason;
            } elseif (isset($responseBody['message'])) {
                $message = is_array($responseBody['message'])
                    ? json_encode($responseBody['message'])
                    : $responseBody['message'];
                $errorMessage .= ' - ' . $message;
            }

            return [
                'success'    => false,
                'message'    => $errorMessage,
                'errors'     => $responseBody['error'] ?? [],
                'debug_data' => $data, // Para debugging
            ];
        }

        return [
            'success' => true,
            'data'    => $response->json()['data'] ?? $response->json(),
        ];
    }

    /**
     * Generar referencia única
     */
    protected function generateReference(Chatbot $chatbot, ?ChatbotProduct $product = null): string
    {
        $prefix = 'TAUSE';
        $chatbotId = str_pad((string) $chatbot->id, 4, '0', STR_PAD_LEFT);
        $productId = $product ? str_pad((string) $product->id, 6, '0', STR_PAD_LEFT) : '000000';
        $timestamp = now()->format('YmdHis');
        $random = Str::upper(Str::random(4));

        return "{$prefix}-{$chatbotId}-{$productId}-{$timestamp}-{$random}";
    }

    /**
     * Obtener URL de redirección después del pago
     */
    protected function getRedirectUrl(Chatbot $chatbot): string
    {
        // URL donde el usuario será redirigido después del pago
        return url("/chatbot/{$chatbot->uuid}/payment-success");
    }

    /**
     * Verificar configuración válida
     */
    protected function hasValidConfiguration(Chatbot $chatbot): bool
    {
        return ! empty($chatbot->wompi_public_key)
            && ! empty($chatbot->wompi_private_key)
            && $chatbot->wompi_enabled;
    }

    /**
     * Obtener private key
     */
    protected function getPrivateKey(Chatbot $chatbot): string
    {
        return $chatbot->wompi_private_key;
    }

    /**
     * Obtener public key
     */
    public function getPublicKey(Chatbot $chatbot): string
    {
        return $chatbot->wompi_public_key;
    }

    /**
     * Testear conexión con Wompi
     */
    public function testConnection(string $publicKey, string $privateKey, string $environment = 'test'): array
    {
        try {
            $baseUrl = $environment === 'production'
                ? self::WOMPI_API_URL
                : self::WOMPI_SANDBOX_URL;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $privateKey,
                'Accept'        => 'application/json',
            ])->timeout(10)->get($baseUrl . '/merchants/' . $publicKey);

            if ($response->successful()) {
                return [
                    'success'     => true,
                    'message'     => '✅ Conexión exitosa con Wompi',
                    'environment' => $environment,
                ];
            }

            return [
                'success' => false,
                'message' => '❌ Error de autenticación. Verifica tus llaves.',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '❌ No se pudo conectar: ' . $e->getMessage(),
            ];
        }
    }
}
