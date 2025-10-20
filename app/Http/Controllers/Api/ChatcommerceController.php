<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Workflows\ChatcommerceWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatcommerceController extends Controller
{
    public function __construct(
        protected ChatcommerceWorkflow $workflow
    ) {}

    /**
     * Handle customer query
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'context' => 'nullable|array',
        ]);

        $query = $request->input('message');
        $context = $request->input('context', []);

        try {
            $result = $this->workflow->handleCustomerQuery($query);

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'context' => $result['context'],
                'next_action' => $result['next_action'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start sales process for a product
     */
    public function startPurchase(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->workflow->startSalesProcess(
                $request->input('product_id'),
                $request->input('quantity')
            );

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'context' => $result['context'],
                'next_action' => $result['next_action'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process customer information
     */
    public function processCustomerInfo(Request $request): JsonResponse
    {
        $request->validate([
            'customer_data' => 'required|array',
            'customer_data.first_name' => 'required|string|max:100',
            'customer_data.last_name' => 'required|string|max:100',
            'customer_data.email' => 'required|email|max:255',
            'customer_data.phone' => 'required|string|max:50',
            'customer_data.address' => 'required|string|max:500',
            'customer_data.city' => 'required|string|max:100',
            'customer_data.department' => 'required|string|max:100',
        ]);

        try {
            $result = $this->workflow->processCustomerInfo($request->input('customer_data'));

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'customer_data' => $result['customer_data'],
                'next_action' => $result['next_action'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create final order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'customer_data' => 'required|array',
        ]);

        try {
            $result = $this->workflow->createFinalOrder(
                $request->input('product_id'),
                $request->input('quantity'),
                $request->input('customer_data')
            );

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'order_data' => $result['order_data'],
                'next_action' => $result['next_action'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

