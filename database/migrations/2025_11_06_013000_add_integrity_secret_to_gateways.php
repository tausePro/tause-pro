<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->string('integrity_secret', 255)->nullable()->after('sandbox_app_id');
        });
        
        // Update Wompi gateways with integrity secrets
        DB::table('gateways')
            ->where('code', 'wompi')
            ->update([
                'integrity_secret' => DB::raw("CASE 
                    WHEN mode = 'live' THEN 'prod_integrity_MXvHSqis7Vn1IFfsSv23QrcW1LS2Gv5V'
                    WHEN mode = 'sandbox' THEN 'test_integrity_6kMUQcvMSVJmvHRyoFEXblQlbWovfc4b'
                    ELSE NULL
                END")
            ]);
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn('integrity_secret');
        });
    }
};
