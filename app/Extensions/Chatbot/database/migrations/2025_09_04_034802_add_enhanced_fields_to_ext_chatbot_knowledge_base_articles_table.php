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
        Schema::table('ext_chatbot_knowledge_base_articles', function (Blueprint $table) {
            $table->json('media_urls')->nullable()->after('content');
            $table->json('product_ids')->nullable()->after('media_urls');
            $table->enum('content_type', ['text', 'multimedia', 'product', 'social'])->default('text')->after('product_ids');
            $table->json('interactive_elements')->nullable()->after('content_type');
            $table->string('source_platform', 50)->nullable()->after('interactive_elements');
            $table->timestamp('last_updated_from_source')->nullable()->after('source_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbot_knowledge_base_articles', function (Blueprint $table) {
            $table->dropColumn([
                'media_urls',
                'product_ids', 
                'content_type',
                'interactive_elements',
                'source_platform',
                'last_updated_from_source'
            ]);
        });
    }
};