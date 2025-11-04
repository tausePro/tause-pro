<?php

namespace App\Services\PaymentGateways;

use App\Actions\CreateActivity;
use App\Enums\Plan\FrequencyEnum;
use App\Enums\Plan\TypeEnum;
use App\Extensions\DiscountManager\System\Models\ConditionalDiscount;
use App\Extensions\DiscountManager\System\Services\DiscountService;
use App\Helpers\Classes\Helper;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Finance\Subscription;
use App\Models\Gateways;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserOrder;
use App\Services\PaymentGateways\Contracts\CreditUpdater;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WompiService
{
    use CreditUpdater;

    protected static string $GATEWAY_CODE = 'wompi';
    protected static string $GATEWAY_NAME = 'Wompi';
    
    private static ?Gateways $gateway = null;
    
    // Wompi API URLs
    private const SANDBOX_URL = 'https://sandbox.wompi.co/v1';
    private const PRODUCTION_URL = 'https://production.wompi.co/v1';

    /**
     * Get Wompi API URL based on environment
     */
    private static function getApiUrl(): string
    {
        $mode = self::getGateway()->mode ?? 'sandbox';
        return $mode === 'live' ? self::PRODUCTION_URL : self::SANDBOX_URL;
    }

    /**
     * Get Wompi public key
     */
    private static function getPublicKey(): string
    {
        return self::getGateway()->live_client_id ?? '';
    }

    /**
     * Get Wompi private key
     */
    private static function getPrivateKey(): string
    {
        return self::getGateway()->live_client_secret ?? '';
    }

    /**
     * Get Wompi events key for webhooks
     */
    private static function getEventsKey(): string
    {
        return self::getGateway()->live_app_id ?? '';
    }

    /**
     * Get gateway instance
     */
    public static function getGateway(): ?Gateways
    {
        if (self::$gateway === null) {
            self::$gateway = Gateways::where('code', self::$GATEWAY_CODE)->first();
        }
        return self::$gateway;
    }

    /**
     * Subscribe user to a plan
     * Compatible with PaymentProcessController interface
     *
     * @param Plan $plan
     * @return \Illuminate\Http\RedirectResponse
     * @throws Exception
     */
    public static function subscribe($plan)
    {
        $user = Auth::user();
        $couponCode = request()->input('coupon');
        
        try {
            DB::beginTransaction();
            
            // Calculate final price with discounts
            $priceData = self::calculateFinalPrice($plan, $couponCode, $user);
            
            // Create order in database
            $order = self::createOrder($user, $plan, $priceData);
            
            // Create Wompi transaction
            $transaction = self::createTransaction($user, $order, $priceData);
            
            DB::commit();
            
            // Redirect to Wompi checkout
            return redirect($transaction['payment_link']);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Wompi Subscribe Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with([
                'message' => 'Error al crear suscripción con Wompi: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Calculate final price with discounts
     *
     * @param Plan $plan
     * @param string|null $couponCode
     * @param User $user
     * @return array
     */
    private static function calculateFinalPrice(Plan $plan, ?string $couponCode, User $user): array
    {
        $originalPrice = $plan->price;
        $discountAmount = 0;
        $finalPrice = $originalPrice;
        $coupon = null;
        $conditionalDiscount = null;
        $discountDuration = null;

        if ($couponCode) {
            // Validate coupon
            $coupon = self::validateCoupon($couponCode, $user);
            
            if ($coupon) {
                // Check if there's a conditional discount
                $conditionalDiscount = ConditionalDiscount::where('coupon_id', $coupon->id)
                    ->where('active', true)
                    ->first();
                
                if ($conditionalDiscount) {
                    // Use DiscountManager for complex discounts
                    $discountService = app(DiscountService::class);
                    $discountAmount = $discountService->calculateDiscount($plan, $conditionalDiscount);
                    $discountDuration = $conditionalDiscount->duration;
                } else {
                    // Use simple coupon discount
                    if ($coupon->is_offer_fixed_price) {
                        $finalPrice = $coupon->discount;
                        $discountAmount = $originalPrice - $finalPrice;
                    } else {
                        $discountAmount = $originalPrice * ($coupon->discount / 100);
                        $finalPrice = $originalPrice - $discountAmount;
                    }
                }
            }
        }

        return [
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => max(0, $finalPrice), // Ensure non-negative
            'coupon' => $coupon,
            'conditional_discount' => $conditionalDiscount,
            'discount_duration' => $discountDuration,
        ];
    }

    /**
     * Validate coupon
     *
     * @param string $code
     * @param User $user
     * @return Coupon|null
     */
    private static function validateCoupon(string $code, User $user): ?Coupon
    {
        $coupon = Coupon::where('code', $code)
            ->where('is_offer', false)
            ->first();

        if (!$coupon) {
            return null;
        }

        // Check usage limit
        if ($coupon->limit != -1 && $coupon->usersUsed->count() >= $coupon->limit) {
            return null;
        }

        // Check if user already used it
        if ($coupon->usersUsed->contains($user->id)) {
            return null;
        }

        return $coupon;
    }

    /**
     * Create order in database
     *
     * @param User $user
     * @param Plan $plan
     * @param array $priceData
     * @return UserOrder
     */
    private static function createOrder(User $user, Plan $plan, array $priceData): UserOrder
    {
        $orderId = 'WMP-' . strtoupper(Str::random(13));
        
        $order = new UserOrder();
        $order->order_id = $orderId;
        $order->plan_id = $plan->id;
        $order->user_id = $user->id;
        $order->payment_type = self::$GATEWAY_CODE;
        $order->price = $priceData['final_price'];
        $order->affiliate_earnings = ($priceData['final_price'] * $plan->affiliate_commission_percentage) / 100;
        $order->status = 'Waiting';
        $order->country = $user->country ?? 'CO';
        
        // Store discount information
        if ($priceData['coupon']) {
            $order->coupon_id = $priceData['coupon']->id;
        }
        
        $order->save();
        
        return $order;
    }

    /**
     * Create Wompi transaction
     *
     * @param User $user
     * @param UserOrder $order
     * @param array $priceData
     * @return array
     * @throws Exception
     */
    private static function createTransaction(User $user, UserOrder $order, array $priceData): array
    {
        $currency = Currency::where('id', setting('default_currency', 'USD'))->first();
        $amountInCents = (int) ($priceData['final_price'] * 100); // Wompi uses cents
        
        $payload = [
            'amount_in_cents' => $amountInCents,
            'currency' => $currency->code ?? 'COP',
            'customer_email' => $user->email,
            'reference' => $order->order_id,
            'redirect_url' => route('dashboard.user.payment.subscription'),
        ];
        
        // Add customer data
        $payload['customer_data'] = [
            'phone_number' => $user->phone ?? '',
            'full_name' => $user->name . ' ' . $user->surname,
        ];
        
        // Add shipping address (optional but recommended)
        $payload['shipping_address'] = [
            'address_line_1' => $user->address ?? 'N/A',
            'country' => $user->country ?? 'CO',
            'phone_number' => $user->phone ?? '',
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . self::getPrivateKey(),
            'Content-Type' => 'application/json',
        ])->post(self::getApiUrl() . '/transactions', $payload);

        if (!$response->successful()) {
            throw new Exception('Wompi API Error: ' . $response->body());
        }

        $data = $response->json()['data'] ?? [];
        
        // Store transaction ID in order
        $order->payment_id = $data['id'] ?? null;
        $order->save();

        return [
            'id' => $data['id'],
            'checkout_url' => $data['payment_link_url'] ?? '',
            'payment_link' => $data['payment_link_url'] ?? '',
        ];
    }

    /**
     * Handle Wompi webhook
     *
     * @param Request $request
     * @return void
     * @throws Exception
     */
    public static function handleWebhook(Request $request): void
    {
        try {
            // Verify webhook signature
            if (!self::verifyWebhookSignature($request)) {
                Log::warning('Wompi: Invalid webhook signature');
                return;
            }

            $event = $request->input('event');
            $data = $request->input('data');
            $transaction = $data['transaction'] ?? [];

            Log::info('Wompi Webhook Received', [
                'event' => $event,
                'transaction_id' => $transaction['id'] ?? null,
                'status' => $transaction['status'] ?? null,
            ]);

            // Handle different event types
            switch ($event) {
                case 'transaction.updated':
                    self::handleTransactionUpdated($transaction);
                    break;
                    
                default:
                    Log::info('Wompi: Unhandled event type: ' . $event);
            }

        } catch (Exception $e) {
            Log::error('Wompi Webhook Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Verify webhook signature
     *
     * @param Request $request
     * @return bool
     */
    private static function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Wompi-Signature');
        $timestamp = $request->header('X-Wompi-Timestamp');
        $payload = $request->getContent();
        
        if (!$signature || !$timestamp) {
            return false;
        }

        $eventsKey = self::getEventsKey();
        $expectedSignature = hash_hmac('sha256', $timestamp . $payload, $eventsKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle transaction updated event
     *
     * @param array $transaction
     * @return void
     * @throws Exception
     */
    private static function handleTransactionUpdated(array $transaction): void
    {
        $reference = $transaction['reference'] ?? null;
        $status = $transaction['status'] ?? null;
        $transactionId = $transaction['id'] ?? null;

        if (!$reference) {
            Log::warning('Wompi: No reference in transaction');
            return;
        }

        $order = UserOrder::where('order_id', $reference)->first();

        if (!$order) {
            Log::warning('Wompi: Order not found', ['reference' => $reference]);
            return;
        }

        // Update order status based on transaction status
        switch ($status) {
            case 'APPROVED':
                self::handleApprovedPayment($order, $transaction);
                break;
                
            case 'DECLINED':
            case 'ERROR':
                $order->status = 'Declined';
                $order->save();
                Log::info('Wompi: Payment declined', ['order_id' => $order->order_id]);
                break;
                
            case 'VOIDED':
                $order->status = 'Cancelled';
                $order->save();
                Log::info('Wompi: Payment voided', ['order_id' => $order->order_id]);
                break;
        }
    }

    /**
     * Handle approved payment
     *
     * @param UserOrder $order
     * @param array $transaction
     * @return void
     * @throws Exception
     */
    private static function handleApprovedPayment(UserOrder $order, array $transaction): void
    {
        DB::beginTransaction();
        
        try {
            $user = $order->user;
            $plan = $order->plan;

            // Update order status
            $order->status = 'Success';
            $order->payment_id = $transaction['id'];
            $order->save();

            // Create or update subscription
            $subscription = Subscription::where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                $subscription = new Subscription();
                $subscription->user_id = $user->id;
                $subscription->plan_id = $plan->id;
            }

            $subscription->stripe_status = 'active';
            $subscription->stripe_id = $transaction['id'];
            $subscription->stripe_price = $order->price;
            $subscription->paid_with = self::$GATEWAY_CODE;
            $subscription->plan_id = $plan->id;
            $subscription->ends_at = null;

            // Calculate next billing date
            if ($plan->frequency == FrequencyEnum::MONTHLY->value) {
                $subscription->auto_renewal_at = Carbon::now()->addMonth();
            } elseif ($plan->frequency == FrequencyEnum::YEARLY->value) {
                $subscription->auto_renewal_at = Carbon::now()->addYear();
            } else {
                $subscription->auto_renewal_at = Carbon::now()->addMonth();
            }

            $subscription->save();

            // Update user credits
            self::updateUserCredits($user, $plan);

            // Mark coupon as used
            if ($order->coupon_id) {
                $coupon = Coupon::find($order->coupon_id);
                if ($coupon && !$coupon->usersUsed->contains($user->id)) {
                    $coupon->usersUsed()->attach($user->id);
                }
            }

            // Create activity log
            CreateActivity::for($user, __('Subscribed'), $plan->name . ' ' . __('Plan'));

            DB::commit();

            Log::info('Wompi: Payment approved and subscription created', [
                'user_id' => $user->id,
                'order_id' => $order->order_id,
                'plan_id' => $plan->id,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Wompi: Error processing approved payment', [
                'order_id' => $order->order_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel subscription
     *
     * @param Subscription $subscription
     * @return bool
     */
    public static function cancelSubscription(Subscription $subscription): bool
    {
        try {
            // Wompi doesn't have automatic recurring payments
            // So we just mark the subscription as cancelled
            $subscription->stripe_status = 'cancelled';
            $subscription->ends_at = Carbon::now();
            $subscription->save();

            Log::info('Wompi: Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Wompi: Error cancelling subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if gateway is available
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        $gateway = self::getGateway();
        return $gateway && $gateway->is_active;
    }

    /**
     * Get payment methods available in Wompi
     *
     * @return array
     */
    public static function getPaymentMethods(): array
    {
        return [
            'CARD' => 'Tarjeta de Crédito/Débito',
            'NEQUI' => 'Nequi',
            'PSE' => 'PSE (Débito Bancario)',
            'BANCOLOMBIA_TRANSFER' => 'Transferencia Bancolombia',
            'BANCOLOMBIA_QR' => 'QR Bancolombia',
        ];
    }

    /**
     * Save all products (compatibility method)
     * Wompi doesn't require pre-creating products like Stripe
     * This method exists for compatibility with the gateway interface
     * 
     * @return bool
     */
    public static function saveAllProducts(): bool
    {
        // Wompi no requiere crear productos previamente
        // Los pagos se crean on-demand con el monto y descripción
        Log::info('WompiService::saveAllProducts() - No action needed, Wompi creates payments on-demand');
        return true;
    }
}
