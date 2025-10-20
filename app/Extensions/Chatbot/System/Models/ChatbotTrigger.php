<?php

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotTrigger extends Model
{
    protected $table = 'ext_chatbot_triggers';

    protected $fillable = [
        'chatbot_id',
        'trigger_type',
        'name',
        'trigger_name',
        'message_template',
        'is_active',
        'is_custom',
        'action',
        'priority',
        'cooldown_minutes',
        'frequency_limit',
        'conditions',
        'display_config',
        'frequency_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'conditions' => 'array',
        'display_config' => 'array',
        'frequency_config' => 'array',
        'priority' => 'integer',
        'cooldown_minutes' => 'integer',
        'frequency_limit' => 'integer',
    ];

    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }
}



