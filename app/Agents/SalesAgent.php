<?php

namespace App\Agents;

use NeuronAI\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\SystemPrompt;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\Toolkit;
use NeuronAI\Chat\Messages\UserMessage;

class SalesAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: config('neuron.providers.openai.key'),
            model: config('neuron.providers.openai.model'),
        );
    }

    protected function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                "You are a sales assistant that helps customers complete their purchases.",
                "You guide customers through the purchase process step by step.",
                "You collect necessary information for shipping and payment.",
                "You are friendly, helpful, and professional.",
                "You always confirm order details before processing payment.",
            ],
            instructions: [
                "When a customer wants to buy a product, guide them through the purchase flow:",
                "1. Confirm the product and quantity",
                "2. Collect shipping information (name, address, phone, email)",
                "3. Collect payment information",
                "4. Confirm the order details",
                "5. Process the order and provide payment link",
                "Always ask for confirmation before proceeding to the next step.",
                "If the customer provides incomplete information, ask for clarification.",
            ]
        );
    }

    protected function tools(): array
    {
        return [
            Toolkit::make([
                Tool::make(
                    name: 'start_purchase',
                    description: 'Start the purchase process for a product',
                    properties: [
                        ToolProperty::make('product_id', 'integer', 'Product ID to purchase'),
                        ToolProperty::make('quantity', 'integer', 'Quantity to purchase'),
                    ],
                    handler: function (array $arguments) {
                        return $this->startPurchase($arguments['product_id'], $arguments['quantity']);
                    }
                ),
                Tool::make(
                    name: 'collect_customer_info',
                    description: 'Collect customer shipping and contact information',
                    properties: [
                        ToolProperty::make('first_name', 'string', 'Customer first name'),
                        ToolProperty::make('last_name', 'string', 'Customer last name'),
                        ToolProperty::make('email', 'string', 'Customer email address'),
                        ToolProperty::make('phone', 'string', 'Customer phone number'),
                        ToolProperty::make('address', 'string', 'Customer shipping address'),
                        ToolProperty::make('city', 'string', 'Customer city'),
                        ToolProperty::make('department', 'string', 'Customer department/state'),
                    ],
                    handler: function (array $arguments) {
                        return $this->collectCustomerInfo($arguments);
                    }
                ),
                Tool::make(
                    name: 'create_order',
                    description: 'Create the final order and generate payment link',
                    properties: [
                        ToolProperty::make('product_id', 'integer', 'Product ID'),
                        ToolProperty::make('quantity', 'integer', 'Quantity'),
                        ToolProperty::make('customer_data', 'object', 'Customer information'),
                    ],
                    handler: function (array $arguments) {
                        return $this->createOrder($arguments['product_id'], $arguments['quantity'], $arguments['customer_data']);
                    }
                ),
            ])
        ];
    }

    /**
     * Start the purchase process
     */
    private function startPurchase(int $productId, int $quantity): array
    {
        return [
            'status' => 'started',
            'product_id' => $productId,
            'quantity' => $quantity,
            'message' => 'Perfecto! Ahora necesito algunos datos para procesar tu pedido.',
            'next_step' => 'collect_customer_info',
        ];
    }

    /**
     * Collect customer information
     */
    private function collectCustomerInfo(array $customerData): array
    {
        return [
            'status' => 'info_collected',
            'customer_data' => $customerData,
            'message' => 'Excelente! Ahora voy a crear tu orden y generar el link de pago.',
            'next_step' => 'create_order',
        ];
    }

    /**
     * Create order and generate payment link
     */
    private function createOrder(int $productId, int $quantity, array $customerData): array
    {
        // TODO: Integrate with WooCommerce and Wompi
        return [
            'status' => 'order_created',
            'order_id' => 'WC-' . rand(1000, 9999),
            'payment_link' => 'https://checkout.wompi.co/l/test-payment-link',
            'total' => 102000 * $quantity,
            'formatted_total' => '$' . number_format(102000 * $quantity, 0, ',', '.') . ' COP',
            'message' => '¡Orden creada exitosamente! Aquí está tu link de pago.',
        ];
    }
}

