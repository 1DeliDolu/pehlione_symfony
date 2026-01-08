<?php

namespace App\Enum;

enum TicketPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function multiplier(): float
    {
        return match ($this) {
            self::LOW => 1.5,
            self::NORMAL => 1.0,
            self::HIGH => 0.6,
            self::URGENT => 0.35,
        };
    }
}
