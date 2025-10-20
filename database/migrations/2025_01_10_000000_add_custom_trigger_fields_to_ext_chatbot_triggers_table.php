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
        Schema::table('ext_chatbot_triggers', function (Blueprint $table) {
            if (!Schema::hasColumn('ext_chatbot_triggers', 'trigger_name')) {
                $table->string('trigger_name')->nullable()->after('trigger_type');
            }
            if (!Schema::hasColumn('ext_chatbot_triggers', 'is_custom')) {
                $table->boolean('is_custom')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('ext_chatbot_triggers', 'frequency_config')) {
                $table->json('frequency_config')->nullable()->after('display_config');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbot_triggers', function (Blueprint $table) {
            if (Schema::hasColumn('ext_chatbot_triggers', 'trigger_name')) {
                $table->dropColumn('trigger_name');
            }
            if (Schema::hasColumn('ext_chatbot_triggers', 'is_custom')) {
                $table->dropColumn('is_custom');
            }
            if (Schema::hasColumn('ext_chatbot_triggers', 'frequency_config')) {
                $table->dropColumn('frequency_config');
            }
        });
    }
};