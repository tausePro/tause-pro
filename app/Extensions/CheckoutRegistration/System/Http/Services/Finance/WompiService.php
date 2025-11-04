<?php

namespace App\Extensions\CheckoutRegistration\System\Http\Services\Finance;

use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentGateways\WompiService as BaseWompiService;
use Illuminate\Http\Request;

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
            'currency' => $gateway->currency ?? 'COP',
            'user_email' => $user->email,
            'user_name' => $user->name . ' ' . $user->surname,
        ];
    }

    /**
     * Handle subscribe checkout
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

        // Use the base WompiService to create subscription
        $result = BaseWompiService::subscribe($user, $plan, $couponCode);

        // Redirect to Wompi checkout
        return redirect($result['checkout_url']);
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
