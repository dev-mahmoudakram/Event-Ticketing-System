<?php

declare(strict_types=1);

namespace App\Enums;

enum LandingPageSection: string
{
    case Hero = 'hero';
    case About = 'about';
    case Stats = 'stats';
    case Location = 'location';
    case AwardsTeaser = 'awards_teaser';
    case Gallery = 'gallery';
}
