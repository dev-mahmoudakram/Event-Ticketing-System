<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case CheckIn = 'check_in';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Admin'),
            self::CheckIn => __('Check-in staff'),
        };
    }
}
