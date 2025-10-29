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
            $table->boolean('negotiation_enabled')->default(false)->after('sales_agent_card_config');
            $table->integer('negotiation_max_discount')->default(10)->after('negotiation_enabled')->comment('Descuento máximo permitido en %');
            $table->integer('negotiation_min_cart_value')->default(50000)->after('negotiation_max_discount')->comment('Valor mínimo del carrito para negociar');
            $table->json('negotiation_triggers')->nullable()->after('negotiation_min_cart_value')->comment('Palabras que activan negociación');
            $table->integer('negotiation_coupon_duration')->default(30)->after('negotiation_triggers')->comment('Duración del cupón en minutos');
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
