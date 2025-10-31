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
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_priority')) {
                $table->integer('sales_agent_priority')->default(5)->after('sales_agent_keywords');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_name')) {
                $table->string('sales_agent_name')->nullable()->after('sales_agent_priority');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_description')) {
                $table->text('sales_agent_description')->nullable()->after('sales_agent_name');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_tone')) {
                $table->string('sales_agent_tone')->default('friendly')->after('sales_agent_description');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_strategy')) {
                $table->string('sales_agent_strategy')->default('helpful')->after('sales_agent_tone');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_search_strategy')) {
                $table->string('sales_agent_search_strategy')->default('semantic')->after('sales_agent_strategy');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_display_mode')) {
                $table->string('sales_agent_display_mode')->default('both')->after('sales_agent_search_strategy');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_custom_prompt')) {
                $table->text('sales_agent_custom_prompt')->nullable()->after('sales_agent_display_mode');
            }
            if (!Schema::hasColumn(self::$prefix . '_chatbots', 'sales_agent_card_config')) {
                $table->json('sales_agent_card_config')->nullable()->after('sales_agent_custom_prompt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(self::$prefix . '_chatbots', function (Blueprint $table) {
            $table->dropColumn([
                'sales_agent_priority',
                'sales_agent_name',
                'sales_agent_description',
                'sales_agent_tone',
                'sales_agent_strategy',
                'sales_agent_search_strategy',
                'sales_agent_display_mode',
                'sales_agent_custom_prompt',
                'sales_agent_card_config',
            ]);
        });
    }
};
