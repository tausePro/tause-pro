<?php

namespace Database\Seeders;

use App\Models\Gateways;
use Illuminate\Database\Seeder;

class WompiGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if Wompi gateway already exists
        $exists = Gateways::where('code', 'wompi')->exists();

        if (!$exists) {
            Gateways::create([
                'code' => 'wompi',
                'title' => 'Wompi',
                'link' => 'https://wompi.co',
                'active' => 0, // Inactive by default
                'available' => 1,
                'img' => '/images/gateways/wompi.svg',
                'whiteLogo' => 0,
                'mode' => 'sandbox', // sandbox or live
                'sandbox_client_id' => '',
                'sandbox_client_secret' => '',
                'sandbox_app_id' => '',
                'live_client_id' => '', // Public Key
                'live_client_secret' => '', // Private Key
                'live_app_id' => '', // Events Key for webhooks
                'currency' => 'COP',
                'currency_locale' => 'es_CO',
                'notify_url' => route('webhooks.wompi'),
                'base_url' => 'https://production.wompi.co/v1',
                'sandbox_url' => 'https://sandbox.wompi.co/v1',
                'tax' => 0,
                'is_active' => 0,
            ]);

            $this->command->info('✅ Wompi gateway created successfully');
        } else {
            $this->command->info('ℹ️  Wompi gateway already exists');
        }
    }
}
