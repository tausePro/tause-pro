<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrateSalesAgentConfigSeeder extends Seeder
{
    /**
     * Migrar configuración existente de Sales Agent a la nueva tabla ext_chatbot_agents
     */
    public function run(): void
    {
        // Obtener todos los chatbots que tienen sales_agent_enabled
        $chatbots = DB::table('ext_chatbots')
            ->whereNotNull('sales_agent_enabled')
            ->get();

        foreach ($chatbots as $chatbot) {
            // 1. Crear agente "External Chatbot" (siempre activo)
            DB::table('ext_chatbot_agents')->insert([
                'chatbot_id' => $chatbot->id,
                'agent_type' => 'external',
                'name' => 'External Chatbot',
                'description' => 'Chatbot conversacional principal con embeddings y knowledge base',
                'is_enabled' => true,
                'priority' => 5, // Prioridad media
                'configuration' => json_encode([
                    'uses_embeddings' => true,
                    'uses_knowledge_base' => true,
                    'max_response_length' => 1500,
                ]),
                'triggers' => json_encode([
                    'type' => 'default',
                    'always_active' => true,
                ]),
                'pricing_tier' => 'free',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Crear agente "Sales Agent" si está habilitado
            if ($chatbot->sales_agent_enabled) {
                $keywords = $chatbot->sales_agent_keywords 
                    ? json_decode($chatbot->sales_agent_keywords, true) 
                    : [];

                DB::table('ext_chatbot_agents')->insert([
                    'chatbot_id' => $chatbot->id,
                    'agent_type' => 'sales',
                    'name' => 'Sales Agent',
                    'description' => 'Agente de ventas con integración WooCommerce y Wompi',
                    'is_enabled' => (bool) $chatbot->sales_agent_enabled,
                    'priority' => 8, // Alta prioridad
                    'configuration' => json_encode([
                        'woocommerce_enabled' => (bool) $chatbot->woocommerce_enabled,
                        'wompi_enabled' => (bool) $chatbot->wompi_enabled,
                        'show_product_grid' => true,
                        'auto_activate' => true,
                    ]),
                    'triggers' => json_encode([
                        'type' => 'keywords',
                        'keywords' => array_merge([
                            'producto', 'productos', 'comprar', 'precio', 'precios',
                            'vender', 'venta', 'disponible', 'stock', 'catálogo',
                        ], $keywords),
                        'detect_commercial_intent' => true,
                    ]),
                    'pricing_tier' => 'premium',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ Configuración de Sales Agent migrada exitosamente');
        $this->command->info("   - Chatbots procesados: {$chatbots->count()}");
    }
}
