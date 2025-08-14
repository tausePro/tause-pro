<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateways\WompiService;
use App\Models\UserOrder;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WompiController extends Controller
{
    private $wompiService;

    public function __construct(WompiService $wompiService)
    {
        $this->wompiService = $wompiService;
    }

    /**
     * Initiate Wompi payment
     */
    public function initiatePayment(Request $request)
    {
        try {
            $request->validate([
                'plan_id' => 'required|exists:plans,id'
            ]);

            $user = Auth::user();
            $plan = Plan::findOrFail($request->plan_id);

            // Create order
            $order = UserOrder::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'payment_method' => 'wompi',
                'amount' => $plan->price,
                'currency' => 'COP',
                'status' => 'pending',
                'order_id' => 'TAUSE-' . time() . '-' . $user->id
            ]);

            // Create payment with Wompi
            $paymentResult = $this->wompiService->createPayment($order, $plan);

            if ($paymentResult['success']) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $paymentResult['payment_url'],
                    'order_id' => $order->id
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $paymentResult['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Wompi payment initiation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'plan_id' => $request->plan_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago'
            ], 500);
        }
    }

    /**
     * Handle payment callback
     */
    public function callback(Request $request)
    {
        try {
            $transactionId = $request->get('id');
            
            if (!$transactionId) {
                return redirect()->route('dashboard')->with('error', 'Pago inválido');
            }

            // Get transaction status from Wompi
            $transaction = $this->wompiService->getTransactionStatus($transactionId);
            
            if (!$transaction) {
                return redirect()->route('dashboard')->with('error', 'No se pudo verificar el pago');
            }

            $reference = $transaction['reference'];
            $orderId = str_replace('TAUSE-', '', $reference);
            $order = UserOrder::find($orderId);

            if (!$order) {
                return redirect()->route('dashboard')->with('error', 'Orden no encontrada');
            }

            switch ($transaction['status']) {
                case 'APPROVED':
                    return redirect()->route('dashboard')->with('success', '¡Pago exitoso! Tu plan ha sido activado.');
                    
                case 'DECLINED':
                    return redirect()->route('dashboard')->with('error', 'El pago fue rechazado. Por favor intenta con otra tarjeta.');
                    
                case 'PENDING':
                    return redirect()->route('dashboard')->with('info', 'Tu pago está siendo procesado. Te notificaremos cuando se complete.');
                    
                default:
                    return redirect()->route('dashboard')->with('error', 'Estado de pago desconocido');
            }

        } catch (\Exception $e) {
            Log::error('Wompi callback error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return redirect()->route('dashboard')->with('error', 'Error al procesar la respuesta del pago');
        }
    }

    /**
     * Handle Wompi webhook
     */
    public function webhook(Request $request)
    {
        return $this->wompiService->handleWebhook($request);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            $order = UserOrder::findOrFail($orderId);

            if ($order->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $transaction = null;
            if ($order->payment_id) {
                $transaction = $this->wompiService->getTransactionStatus($order->payment_id);
            }

            return response()->json([
                'order_status' => $order->status,
                'transaction_status' => $transaction['status'] ?? null,
                'amount' => $order->amount,
                'currency' => $order->currency
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting payment status', [
                'error' => $e->getMessage(),
                'order_id' => $request->get('order_id')
            ]);

            return response()->json(['error' => 'Error getting payment status'], 500);
        }
    }
}