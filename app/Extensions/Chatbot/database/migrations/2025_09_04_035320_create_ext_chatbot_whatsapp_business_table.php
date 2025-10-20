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
        Schema::create('ext_chatbot_whatsapp_business', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('chatbot_id')->nullable()->constrained('ext_chatbots')->cascadeOnDelete();
            $table->string('business_account_id');
            $table->text('access_token');
            $table->string('phone_number_id');
            $table->string('webhook_verify_token');
            $table->json('templates')->nullable();
            $table->json('catalogs')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'chatbot_id']);
            $table->index('business_account_id');
            $table->index('phone_number_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_whatsapp_business');
    }
};