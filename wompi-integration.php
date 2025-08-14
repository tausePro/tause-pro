<?php

namespace App\Services\PaymentGateways;

use App\Models\UserOrder;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WompiService
{
    private $publicKey;
    private $privateKey;
    private $environment;
    private $baseUrl;

    public function __construct()
    {
        $this->publicKey = config('services.wompi.public_key');
        $this->privateKey = config('services.wompi.private_key');
        $this->environment = config('services.wompi.environment', 'sandbox');
        
        $this->baseUrl = $this->environment === 'production' 
            ? 'https://production.wompi.co/v1' 
            : 'https://sandbox.wompi.co/v1';
    }

    /**
     * Create payment intent
     */
    public function createPayment($order, $plan)
    {
        try {
            $payload = [
                'amount_in_cents' => $plan->price * 100, // Convert to cents
                'currency' => 'COP',
                'customer_email' => $order->user->email,
                'reference' => 'TAUSE-' . $order->id,
                'redirect_url' => route('payment.wompi.callback'),
                'payment_method' => [
                    'type' => 'CARD'
                ],
                'customer_data' => [
                    'phone_number' => $order->user->phone ?? '',
                    'full_name' => $order->user->name
                ],
                'shipping_address' => [
                    'address_line_1' => 'Colombia',
                    'country' => 'CO',
                    'region' => 'Bogotá',
                    'city' => 'Bogotá',
                    'name' => $order->user->name,
                    'phone_number' => $order->user->phone ?? ''
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->privateKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/transactions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Store transaction reference
                $order->update([
                    'payment_id' => $data['data']['id'],
                    'status' => 'pending'
                ]);

                return [
                    'success' => true,
                    'payment_url' => $data['data']['payment_link_url'],
                    'transaction_id' => $data['data']['id']
                ];
            }

            Log::error('Wompi payment creation failed', [
                'response' => $response->json(),
                'order_id' => $order->id
            ]);

            return [
                'success' => false,
                'message' => 'Error creating payment'
            ];

        } catch (\Exception $e) {
            Log::error('Wompi payment exception', [
                'error' => $e->getMessage(),
                'order_id' => $order->id
            ]);

            return [
                'success' => false,
                'message' => 'Payment service unavailable'
            ];
        }
    }

    /**
     * Handle webhook from Wompi
     */
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-Signature');

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Invalid Wompi webhook signature');
                return response('Invalid signature', 400);
            }

            $event = $payload['event'];
            $transaction = $payload['data']['transaction'];

            switch ($event) {
                case 'transaction.updated':
                    $this->handleTransactionUpdate($transaction);
                    break;
                    
                default:
                    Log::info('Unhandled Wompi webhook event', ['event' => $event]);
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Wompi webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Handle transaction status update
     */
    private function handleTransactionUpdate($transaction)
    {
        $reference = $transaction['reference'];
        $orderId = str_replace('TAUSE-', '', $reference);
        
        $order = UserOrder::find($orderId);
        
        if (!$order) {
            Log::warning('Order not found for Wompi transaction', [
                'reference' => $reference,
                'transaction_id' => $transaction['id']
            ]);
            return;
        }

        switch ($transaction['status']) {
            case 'APPROVED':
                $this->handleSuccessfulPayment($order, $transaction);
                break;
                
            case 'DECLINED':
            case 'ERROR':
                $this->handleFailedPayment($order, $transaction);
                break;
                
            case 'VOIDED':
                $this->handleVoidedPayment($order, $transaction);
                break;
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment($order, $transaction)
    {
        $order->update([
            'status' => 'completed',
            'payment_id' => $transaction['id'],
            'paid_at' => now()
        ]);

        // Activate user subscription
        $user = $order->user;
        $plan = Plan::find($order->plan_id);

        if ($plan) {
            $user->update([
                'remaining_words' => $plan->total_words,
                'remaining_images' => $plan->total_images,
                'plan_id' => $plan->id
            ]);

            // Create subscription record if needed
            // Add your subscription logic here
        }

        Log::info('Wompi payment completed', [
            'order_id' => $order->id,
            'transaction_id' => $transaction['id'],
            'amount' => $transaction['amount_in_cents'] / 100
        ]);
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment($order, $transaction)
    {
        $order->update([
            'status' => 'failed',
            'payment_id' => $transaction['id']
        ]);

        Log::info('Wompi payment failed', [
            'order_id' => $order->id,
            'transaction_id' => $transaction['id'],
            'reason' => $transaction['status_message'] ?? 'Unknown'
        ]);
    }

    /**
     * Handle voided payment
     */
    private function handleVoidedPayment($order, $transaction)
    {
        $order->update([
            'status' => 'cancelled',
            'payment_id' => $transaction['id']
        ]);

        Log::info('Wompi payment voided', [
            'order_id' => $order->id,
            'transaction_id' => $transaction['id']
        ]);
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature($payload, $signature)
    {
        $webhookSecret = config('services.wompi.webhook_secret');
        
        if (!$webhookSecret) {
            return true; // Skip verification if no secret configured
        }

        $expectedSignature = hash_hmac('sha256', json_encode($payload), $webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus($transactionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->publicKey
            ])->get($this->baseUrl . '/transactions/' . $transactionId);

            if ($response->successful()) {
                return $response->json()['data'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error getting Wompi transaction status', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}