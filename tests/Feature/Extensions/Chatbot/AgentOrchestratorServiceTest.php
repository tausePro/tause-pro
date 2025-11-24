<?php

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotAgent;
use App\Extensions\Chatbot\System\Models\ChatbotProduct;
use App\Extensions\Chatbot\System\Services\AgentOrchestratorService;

beforeEach(function () {
    // Crear usuario para el chatbot
    $user = \App\Models\User::factory()->create();

    // Crear chatbot manualmente (asumiendo que las tablas ya existen)
    $this->chatbot = Chatbot::create([
        'uuid'                => \Illuminate\Support\Str::uuid(),
        'user_id'             => $user->id,
        'title'               => 'Test Chatbot',
        'ai_model'            => 'gpt-4',
        'ai_embedding_model'  => 'text-embedding-ada-002',
        'active'              => true,
        'sales_agent_enabled' => true,
    ]);

    $this->orchestrator = app(AgentOrchestratorService::class);
});

it('retorna estructura básica cuando no hay agentes configurados', function () {
    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: 'Hola, ¿qué productos tienen?',
        aiResponse: 'Tenemos varios productos disponibles'
    );

    expect($result)->toHaveKeys(['message', 'agents_activated', 'orchestration_metadata'])
        ->and($result['agents_activated'])->toBeArray()
        ->and($result['orchestration_metadata']['total_agents'])->toBe(0);
});

it('activa external agent cuando está configurado como always_active', function () {
    ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'external',
        'name'          => 'External Chatbot',
        'is_enabled'    => true,
        'priority'      => 10,
        'triggers'      => ['always_active' => true],
        'configuration' => [],
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: 'Hola',
        aiResponse: 'Hola, ¿en qué puedo ayudarte?'
    );

    expect($result['agents_activated'])->not->toBeEmpty()
        ->and($result['agents_activated'][0]['agent_type'])->toBe('external');
});

it('activa sales agent cuando detecta keywords comerciales', function () {
    ChatbotAgent::create([
        'chatbot_id' => $this->chatbot->id,
        'agent_type' => 'sales',
        'name'       => 'Sales Agent',
        'is_enabled' => true,
        'priority'   => 5,
        'triggers'   => [
            'keywords'                 => ['producto', 'comprar', 'precio'],
            'detect_commercial_intent' => true,
        ],
        'configuration' => [
            'show_product_grid'   => true,
            'woocommerce_enabled' => true,
        ],
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: '¿Qué productos tienen disponibles?',
        aiResponse: 'Tenemos varios productos disponibles para ti'
    );

    expect($result['agents_activated'])->not->toBeEmpty()
        ->and($result['agents_activated'][0]['agent_type'])->toBe('sales');
});

it('no activa agentes deshabilitados', function () {
    ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'sales',
        'name'          => 'Sales Agent Disabled',
        'is_enabled'    => false,
        'priority'      => 5,
        'triggers'      => ['keywords' => ['producto']],
        'configuration' => [],
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: '¿Qué productos tienen?',
        aiResponse: 'Tenemos productos disponibles'
    );

    expect($result['agents_activated'])->toBeEmpty();
});

it('procesa sales agent y encuentra productos mencionados', function () {
    // Crear agente
    $agent = ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'sales',
        'name'          => 'Sales Agent',
        'is_enabled'    => true,
        'priority'      => 5,
        'triggers'      => ['keywords' => ['producto']],
        'configuration' => ['show_product_grid' => true],
    ]);

    // Crear productos manualmente
    $product1 = ChatbotProduct::create([
        'chatbot_id'     => $this->chatbot->id,
        'name'           => 'Producto Test 1',
        'price'          => 100000,
        'product_url'    => 'https://example.com/producto-1',
        'is_active'      => true,
        'in_stock'       => true,
        'stock_quantity' => 10,
    ]);

    $product2 = ChatbotProduct::create([
        'chatbot_id'     => $this->chatbot->id,
        'name'           => 'Producto Test 2',
        'price'          => 200000,
        'product_url'    => 'https://example.com/producto-2',
        'is_active'      => true,
        'in_stock'       => true,
        'stock_quantity' => 10,
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: 'Quiero ver el Producto Test 1',
        aiResponse: 'Te muestro el Producto Test 1 que tenemos disponible'
    );

    expect($result['agents_activated'])->not->toBeEmpty()
        ->and($result['agents_activated'][0]['agent_type'])->toBe('sales')
        ->and($result['agents_activated'][0]['data']['products'])->not->toBeEmpty()
        ->and($result['agents_activated'][0]['data']['products'][0]['name'])->toContain('Producto Test');
});

it('formatea productos con URLs correctas', function () {
    $agent = ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'sales',
        'name'          => 'Sales Agent',
        'is_enabled'    => true,
        'priority'      => 5,
        'triggers'      => ['keywords' => ['producto']],
        'configuration' => [],
    ]);

    $product = ChatbotProduct::create([
        'chatbot_id'     => $this->chatbot->id,
        'name'           => 'Producto con URL',
        'price'          => 150000,
        'product_url'    => 'https://example.com/producto-correcto',
        'is_active'      => true,
        'in_stock'       => true,
        'stock_quantity' => 10,
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: 'Quiero ver Producto con URL',
        aiResponse: 'Aquí está el Producto con URL'
    );

    $formattedProduct = $result['agents_activated'][0]['data']['products'][0] ?? null;

    expect($formattedProduct)->not->toBeNull()
        ->and($formattedProduct['product_url'])->toBe('https://example.com/producto-correcto')
        ->and($formattedProduct['product_url'])->not->toContain('aliviate.com.co');
});

it('no usa URLs del home como fallback', function () {
    $agent = ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'sales',
        'name'          => 'Sales Agent',
        'is_enabled'    => true,
        'priority'      => 5,
        'triggers'      => ['keywords' => ['producto']],
        'configuration' => [],
    ]);

    $product = ChatbotProduct::create([
        'chatbot_id'     => $this->chatbot->id,
        'name'           => 'Producto sin URL válida',
        'price'          => 100000,
        'product_url'    => 'https://www.aliviate.com.co/',
        'is_active'      => true,
        'in_stock'       => true,
        'stock_quantity' => 10,
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: 'Quiero ver Producto sin URL válida',
        aiResponse: 'Aquí está el producto'
    );

    $formattedProduct = $result['agents_activated'][0]['data']['products'][0] ?? null;

    // Si no hay URL válida, debería ser null o una URL de embedding, no el home
    if ($formattedProduct && isset($formattedProduct['product_url'])) {
        expect($formattedProduct['product_url'])->not->toBe('https://www.aliviate.com.co/')
            ->and($formattedProduct['product_url'])->not->toBe('https://aliviate.com.co/');
    }
});

it('respeta prioridad de agentes', function () {
    // Agente con prioridad baja
    ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'support',
        'name'          => 'Support Agent',
        'is_enabled'    => true,
        'priority'      => 3,
        'triggers'      => ['keywords' => ['ayuda']],
        'configuration' => [],
    ]);

    // Agente con prioridad alta
    ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'sales',
        'name'          => 'Sales Agent',
        'is_enabled'    => true,
        'priority'      => 8,
        'triggers'      => ['keywords' => ['producto', 'comprar']],
        'configuration' => [],
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: 'Quiero comprar un producto',
        aiResponse: 'Tenemos productos disponibles'
    );

    expect($result['agents_activated'])->not->toBeEmpty()
        ->and($result['agents_activated'][0]['priority'])->toBeGreaterThanOrEqual(8);
});

it('retorna metadata de orquestación correcta', function () {
    ChatbotAgent::create([
        'chatbot_id'    => $this->chatbot->id,
        'agent_type'    => 'external',
        'name'          => 'External Agent',
        'is_enabled'    => true,
        'priority'      => 10,
        'triggers'      => ['always_active' => true],
        'configuration' => [],
    ]);

    $result = $this->orchestrator->orchestrate(
        chatbot: $this->chatbot,
        userQuery: 'Hola',
        aiResponse: 'Hola'
    );

    expect($result['orchestration_metadata'])->toHaveKeys([
        'total_agents',
        'agents_evaluated',
        'agents_activated_count',
    ])->and($result['orchestration_metadata']['total_agents'])->toBe(1)
        ->and($result['orchestration_metadata']['agents_activated_count'])->toBe(1);
});
