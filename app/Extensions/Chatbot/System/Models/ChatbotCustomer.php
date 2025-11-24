<?php

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotCustomer extends Model
{
    protected $table = 'ext_chatbot_customers';

    protected $fillable = [
        'user_id',
        'avatar',
        'name',
        'email',
        'phone',
        'chatbot_id',
        'session_id',
        'country_code',
        'ip_address',
        'chatbot_channel',
        'payload',
        'gdpr_consent',
        'gdpr_consent_at',
        'crm_tags',
        'crm_status',
        'lead_value',
        'lead_priority',
        'next_action_at',
        'negotiation_notes',
        'quote_payload',
        'last_quote_sent_at',
    ];

    protected $casts = [
        'payload' => 'json',
        'gdpr_consent' => 'boolean',
        'gdpr_consent_at' => 'datetime',
        'crm_tags' => 'array',
        'lead_value' => 'float',
        'next_action_at' => 'datetime',
        'quote_payload' => 'array',
        'last_quote_sent_at' => 'datetime',
    ];
}
