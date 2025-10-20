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
        Schema::table('ext_chatbots', function (Blueprint $table) {
            // WooCommerce Configuration
            $table->string('woocommerce_url')->nullable()->after('gdpr_required');
            $table->text('woocommerce_key')->nullable()->after('woocommerce_url');
            $table->text('woocommerce_secret')->nullable()->after('woocommerce_key');
            $table->boolean('woocommerce_enabled')->default(false)->after('woocommerce_secret');
            $table->timestamp('woocommerce_last_sync')->nullable()->after('woocommerce_enabled');
            
            // Wompi Configuration
            $table->text('wompi_public_key')->nullable()->after('woocommerce_last_sync');
            $table->text('wompi_private_key')->nullable()->after('wompi_public_key');
            $table->boolean('wompi_enabled')->default(false)->after('wompi_private_key');
            $table->enum('wompi_environment', ['test', 'production'])->default('test')->after('wompi_enabled');
            
            // Sales Agent Configuration
            $table->boolean('sales_agent_enabled')->default(false)->after('wompi_environment');
            $table->json('sales_agent_keywords')->nullable()->after('sales_agent_enabled')->comment('Keywords para detectar intención de compra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            $table->dropColumn([
                'woocommerce_url',
                'woocommerce_key',
                'woocommerce_secret',
                'woocommerce_enabled',
                'woocommerce_last_sync',
                'wompi_public_key',
                'wompi_private_key',
                'wompi_enabled',
                'wompi_environment',
                'sales_agent_enabled',
                'sales_agent_keywords',
            ]);
        });
    }
};


