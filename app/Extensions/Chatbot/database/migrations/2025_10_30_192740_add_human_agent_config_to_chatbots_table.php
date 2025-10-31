<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public static $prefix = 'ext';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(self::$prefix . '_chatbots', function (Blueprint $table) {
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'human_agent_command')) {
                $table->string('human_agent_command')->default('humanagent')->after('connect_message');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'human_agent_tip_message')) {
                $table->text('human_agent_tip_message')->nullable()->after('human_agent_command');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'human_agent_tip_sent_at')) {
                $table->timestamp('human_agent_tip_sent_at')->nullable()->after('human_agent_tip_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(self::$prefix . '_chatbots', function (Blueprint $table) {
            $table->dropColumn(['human_agent_command', 'human_agent_tip_message', 'human_agent_tip_sent_at']);
        });
    }
};
