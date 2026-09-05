<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => __('Percentage off'),
            self::Fixed => __('Fixed amount off'),
        };
    }
}
