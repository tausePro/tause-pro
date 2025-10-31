<?php

namespace App\Extensions\MarketingBot\System\Models\Whatsapp;

use Illuminate\Database\Eloquent\Model;

class WhatsappChannel extends Model
{
    protected $table = 'ext_whatsapp_channels';

    protected $fillable = [
        'user_id',
        'provider',
        'whatsapp_sid',
        'whatsapp_token',
        'whatsapp_phone',
        'whatsapp_sandbox_phone',
        'whatsapp_environment',
        'evolution_credentials',
    ];

    protected $casts = [
        'evolution_credentials' => 'array',
    ];

    public function isSandbox(): bool
    {
        return $this->whatsapp_environment === 'sandbox';
    }

    public function isEvolution(): bool
    {
        return $this->provider === 'evolution';
    }

    public function isTwilio(): bool
    {
        return $this->provider === 'twilio' || $this->provider === null;
    }
}
