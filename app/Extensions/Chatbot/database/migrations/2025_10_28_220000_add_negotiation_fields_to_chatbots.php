<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            // Negociación con cupones dinámicos
            // Agregar después de sales_agent_keywords si sales_agent_card_config no existe
            if (!Schema::hasColumn('ext_chatbots', 'negotiation_enabled')) {
                $table->boolean('negotiation_enabled')->default(false);
                $table->integer('negotiation_max_discount')->default(10)->comment('Descuento máximo permitido en %');
                $table->integer('negotiation_min_cart_value')->default(50000)->comment('Valor mínimo del carrito para negociar');
                $table->json('negotiation_triggers')->nullable()->comment('Palabras que activan negociación');
                $table->integer('negotiation_coupon_duration')->default(30)->comment('Duración del cupón en minutos');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            $table->dropColumn([
                'negotiation_enabled',
                'negotiation_max_discount',
                'negotiation_min_cart_value',
                'negotiation_triggers',
                'negotiation_coupon_duration',
            ]);
        });
    }
};
