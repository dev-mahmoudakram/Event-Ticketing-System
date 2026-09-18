<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketRequestFieldType: string
{
    case Instagram = 'instagram';
    case Portfolio = 'portfolio';
    case Cv = 'cv';
    case SocialLink = 'social_link';
}
