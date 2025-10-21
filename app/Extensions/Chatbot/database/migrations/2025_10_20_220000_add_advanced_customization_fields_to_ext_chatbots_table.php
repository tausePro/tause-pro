<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            $table->string('custom_bg_image_url')->nullable()->after('header_bg_image');
            $table->string('welcome_text')->nullable()->after('welcome_message');
            $table->string('initial_prompt_text')->nullable()->after('welcome_text');
            $table->string('cta_button_text')->nullable()->after('initial_prompt_text');
        });
    }

    public function down(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            $table->dropColumn([
                'custom_bg_image_url',
                'welcome_text',
                'initial_prompt_text',
                'cta_button_text',
            ]);
        });
    }
};
