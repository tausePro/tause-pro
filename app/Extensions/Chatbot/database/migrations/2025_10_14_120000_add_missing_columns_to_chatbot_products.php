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
        Schema::table('ext_chatbot_products', function (Blueprint $table) {
            // Agregar columnas faltantes si no existen
            if (!Schema::hasColumn('ext_chatbot_products', 'woocommerce_id')) {
                $table->string('woocommerce_id')->nullable()->after('chatbot_id');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'short_description')) {
                $table->text('short_description')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'regular_price')) {
                $table->decimal('regular_price', 10, 2)->nullable()->after('price');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'sale_price')) {
                $table->decimal('sale_price', 10, 2)->nullable()->after('regular_price');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'gallery_urls')) {
                $table->json('gallery_urls')->nullable()->after('image_url');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'in_stock')) {
                $table->boolean('in_stock')->default(true)->after('gallery_urls');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'categories')) {
                $table->json('categories')->nullable()->after('stock_quantity');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'tags')) {
                $table->json('tags')->nullable()->after('categories');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'product_url')) {
                $table->string('product_url')->nullable()->after('tags');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('metadata');
            }
            
            if (!Schema::hasColumn('ext_chatbot_products', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('last_synced_at');
            }
            
            // Cambiar availability a in_stock boolean si es necesario
            if (Schema::hasColumn('ext_chatbot_products', 'availability')) {
                // Migrar datos de availability a in_stock
                DB::statement('UPDATE ext_chatbot_products SET in_stock = (availability = "in_stock")');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbot_products', function (Blueprint $table) {
            $table->dropColumn([
                'woocommerce_id',
                'short_description',
                'regular_price',
                'sale_price',
                'gallery_urls',
                'in_stock',
                'categories',
                'tags',
                'product_url',
                'last_synced_at',
                'is_active',
            ]);
        });
    }
};

