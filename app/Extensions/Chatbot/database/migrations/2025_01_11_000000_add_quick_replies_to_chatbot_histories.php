<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ext_chatbot_histories', function (Blueprint $table) {
            $table->json('quick_replies')->nullable()->after('message');
            $table->json('metadata')->nullable()->after('quick_replies');
            $table->string('action_type')->nullable()->after('metadata'); // 'text', 'product_card', 'button_action'
        });
    }

    public function down(): void
    {
        Schema::table('ext_chatbot_histories', function (Blueprint $table) {
            $table->dropColumn(['quick_replies', 'metadata', 'action_type']);
        });
    }
};


