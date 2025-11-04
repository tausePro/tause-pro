<?php

namespace App\Extensions\CheckoutRegistration\System\Http\Services\Finance;

use App\Models\Finance\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentGateways\WompiService as BaseWompiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WompiService
{
    /**
     * Create prerequisites if needed
     */
    public function createPreReqsIfNeeded(User $user): void
    {
        // Wompi doesn't require any prerequisites like Stripe customer creation
        // User data is sent directly with each transaction
    }

    /**
     * Get checkout data for registration
     */
    public function checkoutData(User $user, int $planId): array
    {
        $plan = Plan::find($planId);
        
        if (!$plan) {
            throw new \Exception('Plan not found');
        }

        // Get public key for frontend
        $gateway = BaseWompiService::getGateway();
        
        return [
            'gateway' => 'wompi',
            'public_key' => $gateway->live_client_id ?? '',
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_price' => $plan->price,
            'trial_days' => $plan->trial_days ?? 0,
            'currency' => $gateway->currency ?? 'COP',
            'user_email' => $user->email,
            'user_name' => $user->name . ' ' . $user->surname,
        ];
    }

    /**
     * Handle subscribe checkout
     * 
     * IMPORTANTE: Wompi no tiene soporte nativo para trials como Stripe.
     * Si el plan tiene trial_days, se crea la suscripción en estado "trialing"
     * y NO se cobra inmediatamente. El cobro se hará después del trial.
     */
    public function subscribeCheckout(Request $request, $referral = null)
    {
        $user = User::where('email', $request->email)->first();
        $planId = $request->planID;
        $couponCode = $request->coupon_code ?? null;

        if (!$user) {
            throw new \Exception('User not found');
        }

        $plan = Plan::find($planId);
        
        if (!$plan) {
            throw new \Exception('Plan not found');
        }

        // Si el plan tiene trial, crear suscripción en estado trialing sin cobrar
        if ($plan->trial_days && $plan->trial_days > 0) {
            return $this->createTrialSubscription($user, $plan, $couponCode);
        }

        // Si no hay trial, cobrar inmediatamente con Wompi
        $result = BaseWompiService::subscribe($user, $plan, $couponCode);

        // Redirect to Wompi checkout
        return redirect($result['checkout_url']);
    }

    /**
     * Create trial subscription without immediate payment
     * 
     * Durante el trial, el usuario tiene acceso completo.
     * Al finalizar el trial, se debe cobrar automáticamente.
     */
    private function createTrialSubscription(User $user, Plan $plan, ?string $couponCode)
    {
        $trialEndsAt = Carbon::now()->addDays($plan->trial_days);

        // Crear suscripción en estado trialing
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'stripe_status' => 'trialing',
            'stripe_id' => 'wompi_trial_' . uniqid(),
            'stripe_price' => $plan->price,
            'paid_with' => 'wompi',
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => $trialEndsAt,
            'auto_renewal' => 1,
        ]);

        // Dar créditos del plan durante el trial
        if ($plan->type === 'subscription') {
            $user->remaining_words += $plan->total_words;
            $user->remaining_images += $plan->total_images;
            $user->save();
        }

        Log::info('Wompi: Trial subscription created', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'trial_days' => $plan->trial_days,
            'trial_ends_at' => $trialEndsAt,
        ]);

        // Redirigir al dashboard con mensaje de trial activado
        return redirect()->route('dashboard.user.index')->with([
            'message' => __('Trial period activated! You have :days days of free access.', ['days' => $plan->trial_days]),
            'type' => 'success',
        ]);
    }

    /**
     * Handle prepaid checkout
     */
    public function prepaidCheckout(Request $request, $referral = null)
    {
        // For prepaid, use the same flow as subscription
        return $this->subscribeCheckout($request, $referral);
    }
}
