<?php

namespace App\Workflows;

use NeuronAI\Workflow;
use NeuronAI\Workflow\Step;
use NeuronAI\Chat\Messages\UserMessage;
use App\Agents\ProductAgent;
use App\Agents\SalesAgent;

class ChatcommerceWorkflow extends Workflow
{
    protected function steps(): array
    {
        return [
            Step::make('product_recommendation')
                ->agent(ProductAgent::class)
                ->description('Recommend products based on customer query')
                ->condition(fn($context) => !isset($context['purchase_started'])),

            Step::make('sales_process')
                ->agent(SalesAgent::class)
                ->description('Handle the purchase process')
                ->condition(fn($context) => isset($context['purchase_started']) && $context['purchase_started'] === true),
        ];
    }

    /**
     * Handle customer query
     */
    public function handleCustomerQuery(string $query): array
    {
        $context = [
            'query' => $query,
            'purchase_started' => $this->detectPurchaseIntent($query),
        ];

        $response = $this->run($context);

        return [
            'response' => $response->getContent(),
            'context' => $context,
            'next_action' => $this->determineNextAction($context),
        ];
    }

    /**
     * Detect if the customer wants to purchase something
     */
    private function detectPurchaseIntent(string $query): bool
    {
        $purchaseKeywords = [
            'comprar', 'quiero', 'necesito', 'me interesa', 'agregar al carrito',
            'buy', 'want', 'need', 'interested', 'add to cart'
        ];

        $queryLower = strtolower($query);
        
        foreach ($purchaseKeywords as $keyword) {
            if (str_contains($queryLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the next action based on context
     */
    private function determineNextAction(array $context): string
    {
        if ($context['purchase_started']) {
            return 'start_sales_process';
        }

        return 'show_product_recommendations';
    }

    /**
     * Start the sales process for a specific product
     */
    public function startSalesProcess(int $productId, int $quantity = 1): array
    {
        $context = [
            'purchase_started' => true,
            'product_id' => $productId,
            'quantity' => $quantity,
        ];

        $salesAgent = new SalesAgent();
        $response = $salesAgent->chat(
            new UserMessage("Quiero comprar el producto ID {$productId} con cantidad {$quantity}")
        );

        return [
            'response' => $response->getContent(),
            'context' => $context,
            'next_action' => 'collect_customer_info',
        ];
    }

    /**
     * Process customer information
     */
    public function processCustomerInfo(array $customerData): array
    {
        $salesAgent = new SalesAgent();
        $response = $salesAgent->chat(
            new UserMessage("Información del cliente: " . json_encode($customerData))
        );

        return [
            'response' => $response->getContent(),
            'customer_data' => $customerData,
            'next_action' => 'create_order',
        ];
    }

    /**
     * Create final order
     */
    public function createFinalOrder(int $productId, int $quantity, array $customerData): array
    {
        $salesAgent = new SalesAgent();
        $response = $salesAgent->chat(
            new UserMessage("Crear orden para producto {$productId}, cantidad {$quantity}, datos: " . json_encode($customerData))
        );

        return [
            'response' => $response->getContent(),
            'order_data' => [
                'product_id' => $productId,
                'quantity' => $quantity,
                'customer_data' => $customerData,
            ],
            'next_action' => 'payment_link',
        ];
    }
}

