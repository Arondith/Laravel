<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function slaHours(): int
    {
        return match ($this) {
            self::Low => 72,
            self::Medium => 24,
            self::High => 8,
            self::Critical => 2,
        };
    }
}
