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
        Schema::create('ext_chatbot_crm_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained('ext_chatbots')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->enum('trigger_event', ['lead_captured', 'conversation_started', 'email_collected'])->default('lead_captured');
            $table->boolean('active')->default(true);
            $table->json('headers')->nullable();
            $table->timestamps();
        });

        // Add CRM tags to customers
        Schema::table('ext_chatbot_customers', function (Blueprint $table) {
            $table->json('crm_tags')->nullable()->after('gdpr_consent_at');
            $table->string('crm_status')->nullable()->after('crm_tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_crm_webhooks');
        
        Schema::table('ext_chatbot_customers', function (Blueprint $table) {
            $table->dropColumn(['crm_tags', 'crm_status']);
        });
    }
};



