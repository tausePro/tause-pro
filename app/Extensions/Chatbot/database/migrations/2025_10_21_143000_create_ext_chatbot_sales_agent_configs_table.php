<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ext_chatbot_sales_agent_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')
                ->constrained('ext_chatbots')
                ->onDelete('cascade');
            
            // Comportamiento del agente
            $table->boolean('enabled')->default(false);
            $table->string('agent_name')->default('Vendedor');
            $table->text('agent_description')->nullable();
            $table->enum('tone', ['formal', 'casual', 'friendly'])->default('friendly');
            $table->enum('sales_strategy', ['consultative', 'aggressive', 'helpful'])->default('helpful');
            $table->enum('search_strategy', ['keyword', 'semantic', 'hybrid'])->default('semantic');
            $table->enum('product_display_mode', ['conversational', 'cards', 'both'])->default('both');
            
            // Prompt personalizado
            $table->longText('custom_prompt')->nullable();
            
            // Activación automática
            $table->boolean('auto_activate')->default(true);
            $table->json('activation_keywords')->nullable();
            
            // Configuración de tarjetas de productos
            $table->json('product_card_config')->nullable();
            
            $table->timestamps();
            
            // Índices para performance
            $table->index('chatbot_id');
            $table->index('enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_sales_agent_configs');
    }
};
