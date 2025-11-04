<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateways\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WompiWebhookController extends Controller
{
    /**
     * Handle Wompi webhook
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            Log::info('Wompi Webhook Received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
            ]);

            WompiService::handleWebhook($request);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Wompi Webhook Controller Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook'
            ], 500);
        }
    }
}
