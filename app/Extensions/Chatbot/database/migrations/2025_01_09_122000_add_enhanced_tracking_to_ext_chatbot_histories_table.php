<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_chatbot_histories', function (Blueprint $table) {
            $table->json('product_interactions')->nullable()->after('content_type');
            $table->json('multimedia_content')->nullable()->after('product_interactions');
            $table->json('enhanced_metadata')->nullable()->after('multimedia_content');
            
            // Note: JSON columns cannot be directly indexed in MySQL
            // We'll create functional indexes if needed later
        });
    }

    public function down(): void
    {
        Schema::table('ext_chatbot_histories', function (Blueprint $table) {
            $table->dropIndex(['chatbot_id', 'product_interactions']);
            $table->dropIndex(['chatbot_id', 'multimedia_content']);
            
            $table->dropColumn(['product_interactions', 'multimedia_content', 'enhanced_metadata']);
        });
    }
};