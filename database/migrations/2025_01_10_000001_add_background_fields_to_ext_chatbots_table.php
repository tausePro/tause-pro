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
            if (!Schema::hasColumn('ext_chatbots', 'welcome_background')) {
                $table->string('welcome_background')->nullable()->after('color');
            }
            if (!Schema::hasColumn('ext_chatbots', 'welcome_greeting')) {
                $table->string('welcome_greeting')->nullable()->after('welcome_background');
            }
            if (!Schema::hasColumn('ext_chatbots', 'welcome_subtitle')) {
                $table->string('welcome_subtitle')->nullable()->after('welcome_greeting');
            }
            if (!Schema::hasColumn('ext_chatbots', 'welcome_button_text')) {
                $table->string('welcome_button_text')->nullable()->after('welcome_subtitle');
            }
            if (!Schema::hasColumn('ext_chatbots', 'welcome_button_subtitle')) {
                $table->string('welcome_button_subtitle')->nullable()->after('welcome_button_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            $columns = ['welcome_background', 'welcome_greeting', 'welcome_subtitle', 'welcome_button_text', 'welcome_button_subtitle'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('ext_chatbots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};