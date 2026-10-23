<?php

declare(strict_types=1);

namespace App\Enums;

enum VotingMode: string
{
    case Schedule = 'schedule';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Schedule => __('Follow the schedule'),
            self::Open => __('Open now'),
            self::Closed => __('Closed'),
        };
    }
}
