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
            // Verificar si las columnas ya existen antes de agregarlas
            if (! Schema::hasColumn('ext_chatbots', 'epayco_public_key')) {
                $table->string('epayco_public_key')->nullable()->after('wompi_environment');
            }
            if (! Schema::hasColumn('ext_chatbots', 'epayco_private_key')) {
                $table->string('epayco_private_key')->nullable()->after('epayco_public_key');
            }
            if (! Schema::hasColumn('ext_chatbots', 'epayco_enabled')) {
                $table->boolean('epayco_enabled')->default(false)->after('epayco_private_key');
            }
            if (! Schema::hasColumn('ext_chatbots', 'epayco_environment')) {
                $table->string('epayco_environment')->default('test')->after('epayco_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ext_chatbots', function (Blueprint $table) {
            if (Schema::hasColumn('ext_chatbots', 'epayco_environment')) {
                $table->dropColumn('epayco_environment');
            }
            if (Schema::hasColumn('ext_chatbots', 'epayco_enabled')) {
                $table->dropColumn('epayco_enabled');
            }
            if (Schema::hasColumn('ext_chatbots', 'epayco_private_key')) {
                $table->dropColumn('epayco_private_key');
            }
            if (Schema::hasColumn('ext_chatbots', 'epayco_public_key')) {
                $table->dropColumn('epayco_public_key');
            }
        });
    }
};
