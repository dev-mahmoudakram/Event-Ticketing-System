<?php

declare(strict_types=1);

namespace App\Enums;

enum AgendaItemType: string
{
    case Keynote = 'keynote';
    case Session = 'session';
    case WorkshopSession = 'workshop';
    case Break = 'break';
    case Panel = 'panel';

    public function label(): string
    {
        return match ($this) {
            self::Keynote => __('Keynote'),
            self::Session => __('Session'),
            self::WorkshopSession => __('Workshop'),
            self::Break => __('Break'),
            self::Panel => __('Panel'),
        };
    }
}
