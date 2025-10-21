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
        Schema::create('ext_chatbot_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained('ext_chatbots')->onDelete('cascade');
            $table->string('agent_type', 50); // 'external', 'sales', 'support', 'appointment', etc.
            $table->string('name', 100); // Nombre descriptivo del agente
            $table->text('description')->nullable(); // Descripción del agente
            $table->boolean('is_enabled')->default(true); // Si está activo
            $table->integer('priority')->default(5); // Prioridad 1-10 (mayor = más prioridad)
            $table->json('configuration')->nullable(); // Configuración específica del agente
            $table->json('triggers')->nullable(); // Keywords, condiciones de activación
            $table->string('pricing_tier', 20)->default('free'); // 'free', 'basic', 'premium', 'enterprise'
            $table->timestamps();
            
            // Índices
            $table->index(['chatbot_id', 'agent_type']);
            $table->index(['chatbot_id', 'is_enabled']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_agents');
    }
};
