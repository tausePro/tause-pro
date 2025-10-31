<?php

namespace App\Extensions\MarketingBot\System\Console\Commands;

use App\Extensions\MarketingBot\System\Enums\CampaignStatus;
use App\Extensions\MarketingBot\System\Enums\CampaignType;
use App\Extensions\MarketingBot\System\Models\MarketingCampaign;
use App\Extensions\MarketingBot\System\Models\Whatsapp\WhatsappChannel;
use App\Extensions\MarketingBot\System\Services\Whatsapp\EvolutionWhatsappSenderService;
use App\Extensions\MarketingBot\System\Services\Whatsapp\WhatsappSenderService;
use Exception;
use Illuminate\Console\Command;

class RunWhatsappCampaignCommand extends Command
{
    protected $signature = 'app:run-whatsapp-campaign';

    protected $description = 'Run a new Whatsapp campaign';

    public function handle()
    {
        $now = now();

        $campaigns = MarketingCampaign::query()
            ->where('type', CampaignType::whatsapp)
            ->where('status', CampaignStatus::scheduled)
            ->where('scheduled_at', '<=', $now)
            ->get();

        $campaigns->map(function (MarketingCampaign $campaign) {
            try {
                // Obtener el canal de WhatsApp del usuario
                $whatsappChannel = WhatsappChannel::query()
                    ->where('user_id', $campaign->user_id)
                    ->first();

                if (!$whatsappChannel) {
                    $this->error("WhatsApp channel not found for campaign {$campaign->id}");
                    return;
                }

                // Determinar qué servicio usar según el provider
                $provider = $whatsappChannel->provider ?? 'twilio';
                
                $whatsappService = match($provider) {
                    'evolution' => app(EvolutionWhatsappSenderService::class),
                    default => app(WhatsappSenderService::class),
                };

                $this->info("Running campaign {$campaign->id} with provider: {$provider}");

                $whatsappService
                    ->setMarketingCampaign($campaign)
                    ->send();

                $this->info("Campaign {$campaign->id} completed successfully");

            } catch (Exception $e) {
                $this->error("Error in campaign {$campaign->id}: " . $e->getMessage());
            }
        });
    }
}
