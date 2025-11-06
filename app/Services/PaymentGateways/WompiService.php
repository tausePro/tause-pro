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
        $gateway = self::getGateway();
        $mode = $gateway->mode ?? 'sandbox';
        return $mode === 'live' ? ($gateway->live_client_id ?? '') : ($gateway->sandbox_client_id ?? '');
    }

    /**
     * Get Wompi private key
     */
    private static function getPrivateKey(): string
    {
        $gateway = self::getGateway();
        $mode = $gateway->mode ?? 'sandbox';
        return $mode === 'live' ? ($gateway->live_client_secret ?? '') : ($gateway->sandbox_client_secret ?? '');
    }

    /**
     * Get Wompi events key for webhooks
     */
    private static function getEventsKey(): string
    {
        $gateway = self::getGateway();
        $mode = $gateway->mode ?? 'sandbox';
        return $mode === 'live' ? ($gateway->live_app_id ?? '') : ($gateway->sandbox_app_id ?? '');
    }

    /**
     * Get Wompi integrity secret for widget signature
     */
    private static function getIntegritySecret(): string
    {
        $gateway = self::getGateway();
        return $gateway->integrity_secret ?? '';
    }

    /**
     * Generate integrity signature for Wompi Widget
     * Format: hash("sha256", reference + amountInCents + currency + integritySecret)
     */
    private static function generateIntegritySignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        $integritySecret = self::getIntegritySecret();
        $concatenated = $reference . $amountInCents . $currency . $integritySecret;
        return hash('sha256', $concatenated);
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
     * Returns view with payment button like PayPal/Stripe
     *
     * @param Plan $plan
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     * @throws Exception
     */
    public static function subscribe($plan)
    {
        $gateway = self::getGateway();
        $user = Auth::user();
        $coupon = checkCouponInRequest();
        
        try {
            // Get gateway currency
            $gatewayCurrency = Currency::where('id', $gateway->currency)->first();
            
            // If gateway is in COP, use plan price as-is (no conversion needed)
            // If gateway is in another currency, convert to COP
            $priceInCOP = $plan->price;
            
            if ($gatewayCurrency && $gatewayCurrency->code !== 'COP') {
                // Plan is in different currency, convert to COP
                // Fixed exchange rate: 1 USD = 4000 COP
                $exchangeRate = 4000;
                $priceInCOP = $plan->price * $exchangeRate;
            }
            
            // Calculate prices
            $newDiscountedPrice = $priceInCOP;
            if ($coupon) {
                $newDiscountedPrice = $priceInCOP - ($priceInCOP * ($coupon->discount / 100));
            }
            
            $taxRate = $gateway->tax ?? 0;
            $taxValue = taxToVal($newDiscountedPrice, $taxRate);
            $finalPrice = $newDiscountedPrice;
            
            // Generate unique reference for this transaction
            $reference = 'WMP-' . strtoupper(Str::random(13));
            
            // Amount in cents (Wompi requirement)
            $amountInCents = (int) ($finalPrice * 100);
            
            // Generate integrity signature for Widget
            $integritySignature = self::generateIntegritySignature($reference, $amountInCents, 'COP');
            
            // Prepare widget data
            $publicKey = self::getPublicKey();
            
            Log::info('Wompi Widget Data Preparation', [
                'public_key' => $publicKey,
                'gateway_mode' => $gateway->mode,
                'reference' => $reference,
                'amount_in_cents' => $amountInCents
            ]);
            
            $widgetData = [
                'public_key' => $publicKey,
                'currency' => 'COP',
                'amount_in_cents' => $amountInCents,
                'reference' => $reference,
                'integrity_signature' => $integritySignature,
                'redirect_url' => route('dashboard.user.payment.succesful'),
            ];
            
            // Create order in database with status 'Waiting'
            $order = new UserOrder();
            $order->order_id = $reference;
            $order->plan_id = $plan->id;
            $order->user_id = $user->id;
            $order->payment_type = self::$GATEWAY_CODE;
            $order->price = $finalPrice;
            $order->affiliate_earnings = ($finalPrice * Helper::setting('affiliate_commission_percentage')) / 100;
            $order->status = 'Waiting';
            $order->country = $user->country ?? 'CO';
            $order->tax_rate = $taxRate;
            $order->tax_value = $taxValue;
            
            if ($coupon) {
                $order->coupon_id = $coupon->id;
            }
            
            $order->save();
            
            Log::info('Wompi Widget Payment Prepared', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'reference' => $reference,
                'amount_in_cents' => $amountInCents,
                'order_id' => $order->id
            ]);
            
            // Return view with Widget
            return view('panel.user.finance.subscription.' . self::$GATEWAY_CODE, compact(
                'plan',
                'gateway',
                'finalPrice',
                'taxValue',
                'taxRate',
                'coupon',
                'widgetData'
            ));
            
        } catch (Exception $e) {
            Log::error('Wompi Subscribe Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with([
                'message' => 'Error al preparar pago con Wompi: ' . $e->getMessage(),
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
    public static function calculateFinalPrice(Plan $plan, ?string $couponCode, User $user): array
    {
        $gateway = self::getGateway();
        $gatewayCurrency = Currency::where('id', $gateway->currency)->first();
        
        // If gateway is in COP, use plan price as-is
        $originalPrice = $plan->price;
        
        if ($gatewayCurrency && $gatewayCurrency->code !== 'COP') {
            // Convert to COP if needed
            $exchangeRate = 4000; // 1 USD = 4000 COP
            $originalPrice = $plan->price * $exchangeRate;
        }
        
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
    public static function createOrder(User $user, Plan $plan, array $priceData): UserOrder
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
    public static function createTransaction(User $user, UserOrder $order, array $priceData): array
    {
        $gateway = self::getGateway();
        
        // Get currency from gateway settings (COP for Wompi)
        $currency = Currency::where('id', $gateway->currency)->first();
        if (!$currency) {
            $currency = Currency::where('code', 'COP')->first();
        }
        
        $amountInCents = (int) ($priceData['final_price'] * 100); // Wompi uses cents
        
        // Use payment_links endpoint instead of transactions
        // This generates a checkout page where user selects payment method
        $payload = [
            'name' => $order->plan->name ?? 'Subscription Plan',
            'description' => 'Suscripción a ' . ($order->plan->name ?? 'Plan'),
            'single_use' => true,
            'collect_shipping' => false,
            'currency' => $currency->code ?? 'COP',
            'amount_in_cents' => $amountInCents,
            'redirect_url' => route('dashboard.user.payment.succesful'),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . self::getPrivateKey(), // Use private key for payment links
            'Content-Type' => 'application/json',
        ])->post(self::getApiUrl() . '/payment_links', $payload);

        if (!$response->successful()) {
            throw new Exception('Wompi API Error: ' . $response->body());
        }

        $data = $response->json()['data'] ?? [];
        
        // Store payment link ID in order
        $order->payment_id = $data['id'] ?? null;
        $order->save();

        return [
            'id' => $data['id'],
            'checkout_url' => $data['url'] ?? '',
            'payment_link' => $data['url'] ?? '',
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
        // Wompi uses x-event-checksum header for webhook validation
        $checksum = $request->header('x-event-checksum');
        $payload = $request->getContent();
        
        // Log full payload for debugging
        $payloadData = json_decode($payload, true);
        
        Log::info('Wompi Webhook Signature Verification', [
            'checksum_received' => $checksum,
            'payload_length' => strlen($payload),
            'payload_preview' => substr($payload, 0, 200),
            'payload_data' => $payloadData,
            'headers' => $request->headers->all()
        ]);
        
        if (!$checksum) {
            Log::warning('Wompi: Missing x-event-checksum header');
            return false;
        }

        // Get events key (secret for webhook validation)
        $eventsKey = self::getEventsKey();
        
        if (empty($eventsKey)) {
            Log::error('Wompi: Events key not configured');
            return false;
        }
        
        Log::info('Wompi Events Key Info', [
            'events_key_length' => strlen($eventsKey),
            'events_key_prefix' => substr($eventsKey, 0, 15)
        ]);
        
        // According to Wompi docs: checksum is calculated using specific properties
        // Extract signature properties from payload
        $signatureProperties = $payloadData['signature']['properties'] ?? [];
        $timestamp = $payloadData['timestamp'] ?? '';
        
        // Build concatenated string from signature properties
        $concatenatedValues = '';
        $transaction = $payloadData['data']['transaction'] ?? [];
        
        foreach ($signatureProperties as $property) {
            // Handle nested properties like "transaction.id"
            $keys = explode('.', $property);
            $value = $transaction;
            
            foreach ($keys as $key) {
                if ($key === 'transaction') continue; // Skip 'transaction' prefix
                $value = $value[$key] ?? '';
            }
            
            $concatenatedValues .= $value;
        }
        
        // Calculate checksum: SHA256(concatenated_values + timestamp + events_secret)
        $expectedChecksum = hash('sha256', $concatenatedValues . $timestamp . $eventsKey);
        $matched = hash_equals($expectedChecksum, $checksum);
        
        Log::info('Wompi Checksum Comparison', [
            'timestamp' => $timestamp,
            'signature_properties' => $signatureProperties,
            'concatenated_values' => $concatenatedValues,
            'expected_checksum' => $expectedChecksum,
            'received' => $checksum,
            'matched' => $matched
        ]);

        return $matched;
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
                ->where('stripe_status', 'active')
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
            $subscription->auto_renewal = 0; // Wompi doesn't have automatic recurring
            $subscription->name = $plan->name;
            $subscription->quantity = 1;
            
            // Set trial period if plan has it
            if ($plan->trial_days > 0) {
                $subscription->trial_ends_at = Carbon::now()->addDays($plan->trial_days);
                $subscription->ends_at = Carbon::now()->addDays($plan->trial_days);
            } else {
                // Set ends_at based on plan frequency (default to monthly = 30 days)
                $frequency = $plan->frequency ?? 'monthly';
                $daysToAdd = match($frequency) {
                    'monthly' => 30,
                    'yearly', 'annual' => 365,
                    'weekly' => 7,
                    'daily' => 1,
                    'lifetime' => 3650, // 10 years
                    default => 30
                };
                $subscription->ends_at = Carbon::now()->addDays($daysToAdd);
            }

            $subscription->save();

            // Clear cache to ensure getCurrentActiveSubscription picks up the new subscription
            cache()->forget('active_subscription_' . $user->id);
            
            // Update user credits using trait method
            self::creditIncreaseSubscribePlan($user, $plan);

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
     * Get subscription status
     *
     * @param int|null $incomingUserId
     * @return bool
     */
    public static function getSubscriptionStatus($incomingUserId = null)
    {
        if ($incomingUserId != null) {
            $user = User::where('id', $incomingUserId)->first();
        } else {
            $user = Auth::user();
        }
        
        $sub = getCurrentActiveSubscription($user->id);
        if ($sub != null) {
            return true;
        }

        return false;
    }

    /**
     * Get subscription days left
     *
     * @return int
     */
    public static function getSubscriptionDaysLeft()
    {
        $user = Auth::user();
        $sub = getCurrentActiveSubscription($user->id);
        
        if ($sub) {
            return Carbon::now()->diffInDays($sub->ends_at);
        } else {
            Log::error('WompiService: getSubscriptionDaysLeft() - No active subscription found');
            return 0;
        }
    }

    /**
     * Check if subscription is in trial period
     *
     * @return bool
     */
    public static function checkIfTrial()
    {
        $user = Auth::user();
        $activeSub = getCurrentActiveSubscription($user->id);
        
        if ($activeSub != null) {
            if ($activeSub->trial_ends_at != null) {
                return Carbon::now()->lessThan(Carbon::parse($activeSub->trial_ends_at));
            }
        }
        
        return false;
    }

    /**
     * Cancel user subscription (called from user dashboard)
     *
     * @param User|null $internalUser
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function subscribeCancel($internalUser = null)
    {
        $user = $internalUser ?? Auth::user();
        $activeSub = getCurrentActiveSubscription($user->id);
        
        if ($activeSub != null) {
            $plan = Plan::where('id', $activeSub->plan_id)->first();

            self::creditDecreaseCancelPlan($user, $plan);

            $activeSub->stripe_status = 'cancelled';
            $activeSub->ends_at = Carbon::now();
            $activeSub->save();

            CreateActivity::for($user, 'cancelled', $plan->name);
            
            if ($internalUser != null) {
                return back()->with(['message' => __('User subscription is cancelled succesfully.'), 'type' => 'success']);
            }

            return redirect()->route('dashboard.user.index')->with(['message' => __('Your subscription is cancelled succesfully.'), 'type' => 'success']);
        }

        return back()->with(['message' => __('Could not find active subscription. Nothing changed!'), 'type' => 'error']);
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
     * Save product to Wompi
     * Wompi doesn't require pre-creating products like Stripe
     * Products are created on-demand when payment link is generated
     *
     * @param Plan $plan
     * @return void
     */
    public static function saveProduct($plan): void
    {
        // Wompi doesn't need to pre-create products
        // Payment links are created on-demand with plan details
        Log::info('WompiService::saveProduct() - No action needed, Wompi creates payments on-demand', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
        ]);
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
