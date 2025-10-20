<?php

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotCrmWebhook extends Model
{
    protected $table = 'ext_chatbot_crm_webhooks';

    protected $fillable = [
        'chatbot_id',
        'name',
        'url',
        'trigger_event',
        'active',
        'headers',
    ];

    protected $casts = [
        'active' => 'boolean',
        'headers' => 'array',
    ];

    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }
}



