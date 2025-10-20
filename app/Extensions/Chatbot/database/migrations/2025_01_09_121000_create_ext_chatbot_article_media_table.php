<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_chatbot_article_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('ext_chatbot_knowledge_base_articles')
                ->onDelete('cascade');
            $table->foreignId('media_file_id')
                ->constrained('ext_content_manager_media_files')
                ->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['article_id', 'media_file_id']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_article_media');
    }
};