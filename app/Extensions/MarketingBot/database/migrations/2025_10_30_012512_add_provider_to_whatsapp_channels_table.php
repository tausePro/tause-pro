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
        Schema::table('ext_whatsapp_channels', function (Blueprint $table) {
            $table->string('provider')->default('twilio')->after('user_id')->comment('twilio or evolution');
            $table->json('evolution_credentials')->nullable()->after('whatsapp_token')->comment('Evolution API credentials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_whatsapp_channels', function (Blueprint $table) {
            $table->dropColumn(['provider', 'evolution_credentials']);
        });
    }
};
