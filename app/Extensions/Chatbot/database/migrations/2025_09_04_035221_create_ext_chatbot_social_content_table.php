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
        Schema::create('ext_chatbot_social_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('chatbot_id')->nullable()->constrained('ext_chatbots')->cascadeOnDelete();
            $table->enum('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'youtube']);
            $table->string('platform_post_id')->nullable();
            $table->text('original_content');
            $table->text('processed_content')->nullable();
            $table->json('engagement_metrics')->nullable();
            $table->string('sentiment', 20)->nullable();
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'chatbot_id']);
            $table->index(['platform', 'posted_at']);
            $table->unique(['platform', 'platform_post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_social_content');
    }
};