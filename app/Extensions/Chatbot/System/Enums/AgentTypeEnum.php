<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Enums;

use App\Enums\Traits\EnumTo;

enum AgentTypeEnum: string
{
    use EnumTo;

    case SALES = 'sales';
    case SUPPORT = 'support';
    case APPOINTMENT = 'appointment';
    case LEAD_CAPTURE = 'lead_capture';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::SALES => 'Sales Agent',
            self::SUPPORT => 'Support Agent',
            self::APPOINTMENT => 'Appointment Agent',
            self::LEAD_CAPTURE => 'Lead Capture Agent',
            self::CUSTOM => 'Custom Agent',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SALES => 'Helps customers find and buy products',
            self::SUPPORT => 'Provides technical support and answers questions',
            self::APPOINTMENT => 'Schedules appointments and manages calendar',
            self::LEAD_CAPTURE => 'Captures lead information and qualifies prospects',
            self::CUSTOM => 'Custom agent with user-defined behavior',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SALES => '🤖',
            self::SUPPORT => '💬',
            self::APPOINTMENT => '📅',
            self::LEAD_CAPTURE => '📋',
            self::CUSTOM => '⚙️',
        };
    }

    public function defaultKeywords(): array
    {
        return match ($this) {
            self::SALES => ['comprar', 'precio', 'producto', 'vender', 'costo', 'pagar', 'buy', 'price', 'product'],
            self::SUPPORT => ['ayuda', 'problema', 'error', 'soporte', 'help', 'issue', 'support'],
            self::APPOINTMENT => ['cita', 'agendar', 'reservar', 'appointment', 'schedule', 'book'],
            self::LEAD_CAPTURE => ['contacto', 'información', 'cotizar', 'contact', 'info', 'quote'],
            self::CUSTOM => [],
        };
    }

    public function defaultPriority(): int
    {
        return match ($this) {
            self::SALES => 10,
            self::SUPPORT => 8,
            self::APPOINTMENT => 7,
            self::LEAD_CAPTURE => 6,
            self::CUSTOM => 5,
        };
    }

    public function requiresPro(): bool
    {
        return match ($this) {
            self::SALES => false, // Available in Starter
            self::SUPPORT => true,
            self::APPOINTMENT => true,
            self::LEAD_CAPTURE => true,
            self::CUSTOM => true,
        };
    }
}
