<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\ChatbotTrigger;
use Illuminate\Support\Facades\Cache;

class ProactiveTriggerService
{
    public function getActiveTriggers(string $chatbotId): array
    {
        return Cache::remember("chatbot_triggers_{$chatbotId}", 3600, function () use ($chatbotId) {
            return ChatbotTrigger::where('chatbot_id', $chatbotId)
                ->where('is_active', true)
                ->orderBy('priority', 'asc')
                ->get()
                ->map(function ($trigger) {
                    return [
                        'id' => $trigger->id,
                        'type' => $trigger->trigger_type,
                        'message' => $trigger->message_template,
                        'action' => $trigger->action,
                        'priority' => $trigger->priority,
                        'cooldown' => $trigger->cooldown_minutes,
                        'conditions' => $trigger->conditions ?? [],
                        'display_config' => $trigger->display_config ?? [],
                    ];
                })
                ->toArray();
        });
    }

    public function clearTriggerCache(string $chatbotId): void
    {
        Cache::forget("chatbot_triggers_{$chatbotId}");
    }

    public function evaluateTrigger(string $triggerType, array $context, string $chatbotId): ?array
    {
        $trigger = ChatbotTrigger::where('chatbot_id', $chatbotId)
            ->where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->first();

        if (!$trigger) {
            return null;
        }

        return [
            'id' => $trigger->id,
            'type' => $trigger->trigger_type,
            'message' => $this->replacePlaceholders($trigger->message_template, $context),
            'action' => $trigger->action,
            'display_config' => $trigger->display_config ?? [],
        ];
    }

    protected function replacePlaceholders(string $message, array $context): string
    {
        foreach ($context as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        return $message;
    }
}



