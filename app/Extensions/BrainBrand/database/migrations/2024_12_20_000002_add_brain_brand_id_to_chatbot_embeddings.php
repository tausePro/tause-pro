<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_chatbot_embeddings', function (Blueprint $table) {
            // Add brain_brand_id column to link embeddings to Brain Brand
            $table->unsignedBigInteger('brain_brand_id')->nullable()->after('chatbot_id');
            $table->foreign('brain_brand_id')->references('id')->on('ext_brain_brands')->onDelete('set null');
            $table->index('brain_brand_id');
        });
    }

    public function down(): void
    {
        Schema::table('ext_chatbot_embeddings', function (Blueprint $table) {
            $table->dropForeign(['brain_brand_id']);
            $table->dropIndex(['brain_brand_id']);
            $table->dropColumn('brain_brand_id');
        });
    }
};
