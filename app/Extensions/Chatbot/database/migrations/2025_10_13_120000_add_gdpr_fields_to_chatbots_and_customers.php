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
        // Add GDPR fields to chatbots table
        Schema::table('ext_chatbots', function (Blueprint $table) {
            $table->boolean('gdpr_enabled')->default(false)->after('is_links');
            $table->text('gdpr_message')->nullable()->after('gdpr_enabled');
            $table->boolean('gdpr_required')->default(true)->after('gdpr_message');
        });

        // Add GDPR consent field to customers table
        Schema::table('ext_chatbot_customers', function (Blueprint $table) {
            $table->boolean('gdpr_consent')->default(false)->after('payload');
            $table->timestamp('gdpr_consent_at')->nullable()->after('gdpr_consent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            $table->dropColumn(['gdpr_enabled', 'gdpr_message', 'gdpr_required']);
        });

        Schema::table('ext_chatbot_customers', function (Blueprint $table) {
            $table->dropColumn(['gdpr_consent', 'gdpr_consent_at']);
        });
    }
};



