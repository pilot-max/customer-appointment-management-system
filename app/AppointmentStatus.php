<?php

namespace App;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function badgeColor(): string
    {
        return match ($this) {
            self::Scheduled => 'blue',
            self::Cancelled => 'zinc',
            self::Completed => 'green',
        };
    }
}
