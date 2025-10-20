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
        Schema::create('ext_chatbot_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained('ext_chatbots')->cascadeOnDelete();
            
            // WooCommerce data
            $table->string('woocommerce_id')->nullable(); // ID del producto en WooCommerce
            $table->string('sku')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            
            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            
            // Images
            $table->string('image_url')->nullable(); // URL de la imagen principal
            $table->json('gallery_urls')->nullable(); // Array de URLs de galería
            
            // Stock
            $table->boolean('in_stock')->default(true);
            $table->integer('stock_quantity')->nullable();
            
            // Categories & Tags
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            
            // Product URL
            $table->string('product_url')->nullable(); // URL del producto en la tienda
            
            // Metadata
            $table->json('metadata')->nullable(); // Datos adicionales de WooCommerce
            
            // Sync control
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('chatbot_id');
            $table->index('woocommerce_id');
            $table->index('sku');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_products');
    }
};


