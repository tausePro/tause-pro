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
        Schema::table('ext_chatbot_customers', function (Blueprint $table) {
            if (! Schema::hasColumn('ext_chatbot_customers', 'lead_value')) {
                $table->decimal('lead_value', 12, 2)->nullable()->after('crm_status');
            }

            if (! Schema::hasColumn('ext_chatbot_customers', 'lead_priority')) {
                $table->string('lead_priority')->default('medium')->after('lead_value');
            }

            if (! Schema::hasColumn('ext_chatbot_customers', 'next_action_at')) {
                $table->timestamp('next_action_at')->nullable()->after('lead_priority');
            }

            if (! Schema::hasColumn('ext_chatbot_customers', 'negotiation_notes')) {
                $table->text('negotiation_notes')->nullable()->after('next_action_at');
            }

            if (! Schema::hasColumn('ext_chatbot_customers', 'quote_payload')) {
                $table->json('quote_payload')->nullable()->after('negotiation_notes');
            }

            if (! Schema::hasColumn('ext_chatbot_customers', 'last_quote_sent_at')) {
                $table->timestamp('last_quote_sent_at')->nullable()->after('quote_payload');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbot_customers', function (Blueprint $table) {
            if (Schema::hasColumn('ext_chatbot_customers', 'last_quote_sent_at')) {
                $table->dropColumn('last_quote_sent_at');
            }

            if (Schema::hasColumn('ext_chatbot_customers', 'quote_payload')) {
                $table->dropColumn('quote_payload');
            }

            if (Schema::hasColumn('ext_chatbot_customers', 'negotiation_notes')) {
                $table->dropColumn('negotiation_notes');
            }

            if (Schema::hasColumn('ext_chatbot_customers', 'next_action_at')) {
                $table->dropColumn('next_action_at');
            }

            if (Schema::hasColumn('ext_chatbot_customers', 'lead_priority')) {
                $table->dropColumn('lead_priority');
            }

            if (Schema::hasColumn('ext_chatbot_customers', 'lead_value')) {
                $table->dropColumn('lead_value');
            }
        });
    }
};
