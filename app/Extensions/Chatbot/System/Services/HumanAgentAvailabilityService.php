<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use Carbon\Carbon;
use DateTimeZone;

class HumanAgentAvailabilityService
{
    /**
     * Evaluate availability metadata for a chatbot.
     */
    public function evaluate(Chatbot $chatbot): array
    {
        $timezone = $chatbot->human_agent_timezone ?: config('app.timezone');
        $schedule = $chatbot->human_agent_schedule ?? [];
        $scheduleEnabled = (bool) ($chatbot->human_agent_schedule_enabled ?? false);
        $aiEnabled = (bool) ($chatbot->ai_handling_enabled ?? true);

        $now = Carbon::now(new DateTimeZone($timezone));
        $dayKey = strtolower($now->format('l'));
        $slot = collect($schedule)
            ->map(function ($entry) {
                return [
                    'day'     => strtolower(data_get($entry, 'day')),
                    'start'   => data_get($entry, 'start'),
                    'end'     => data_get($entry, 'end'),
                    'enabled' => (bool) data_get($entry, 'enabled', true),
                ];
            })
            ->first(fn ($entry) => $entry['day'] === $dayKey);

        $withinSchedule = true;

        if ($scheduleEnabled) {
            if (! $slot || ! $slot['enabled'] || ! $slot['start'] || ! $slot['end']) {
                $withinSchedule = false;
            } else {
                $currentTime = $now->format('H:i');
                $withinSchedule = $currentTime >= $slot['start'] && $currentTime <= $slot['end'];
            }
        }

        return [
            'ai_enabled'        => $aiEnabled,
            'schedule_enabled'  => $scheduleEnabled,
            'within_schedule'   => $withinSchedule,
            'slot'              => $slot,
            'timezone'          => $timezone,
            'current_time'      => $now,
            'day_key'           => $dayKey,
        ];
    }

    public function aiShouldHandle(Chatbot $chatbot): bool
    {
        return $this->evaluate($chatbot)['ai_enabled'];
    }

    public function humanAgentsAvailable(Chatbot $chatbot): bool
    {
        $data = $this->evaluate($chatbot);

        return ! $data['schedule_enabled'] || $data['within_schedule'];
    }
}

