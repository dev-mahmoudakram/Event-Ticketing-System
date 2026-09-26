<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketRequestFieldType: string
{
    case Instagram = 'instagram';
    case Portfolio = 'portfolio';
    case Cv = 'cv';
    case SocialLink = 'social_link';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => __('Instagram'),
            self::Portfolio => __('Portfolio'),
            self::Cv => __('CV'),
            self::SocialLink => __('Social link'),
        };
    }
}
