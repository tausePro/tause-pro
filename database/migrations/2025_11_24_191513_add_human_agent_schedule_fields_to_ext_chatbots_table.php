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
        Schema::table('ext_chatbots', function (Blueprint $table) {
            if (! Schema::hasColumn('ext_chatbots', 'ai_handling_enabled')) {
                $table->boolean('ai_handling_enabled')->default(true)->after('interaction_type');
            }

            if (! Schema::hasColumn('ext_chatbots', 'human_agent_schedule_enabled')) {
                $table->boolean('human_agent_schedule_enabled')->default(false)->after('ai_handling_enabled');
            }

            if (! Schema::hasColumn('ext_chatbots', 'human_agent_timezone')) {
                $table->string('human_agent_timezone')->nullable()->after('human_agent_schedule_enabled');
            }

            if (! Schema::hasColumn('ext_chatbots', 'human_agent_schedule')) {
                $table->json('human_agent_schedule')->nullable()->after('human_agent_timezone');
            }

            if (! Schema::hasColumn('ext_chatbots', 'human_agent_offline_message')) {
                $table->text('human_agent_offline_message')->nullable()->after('human_agent_tip_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            if (Schema::hasColumn('ext_chatbots', 'human_agent_offline_message')) {
                $table->dropColumn('human_agent_offline_message');
            }

            if (Schema::hasColumn('ext_chatbots', 'human_agent_schedule')) {
                $table->dropColumn('human_agent_schedule');
            }

            if (Schema::hasColumn('ext_chatbots', 'human_agent_timezone')) {
                $table->dropColumn('human_agent_timezone');
            }

            if (Schema::hasColumn('ext_chatbots', 'human_agent_schedule_enabled')) {
                $table->dropColumn('human_agent_schedule_enabled');
            }

            if (Schema::hasColumn('ext_chatbots', 'ai_handling_enabled')) {
                $table->dropColumn('ai_handling_enabled');
            }
        });
    }
};
