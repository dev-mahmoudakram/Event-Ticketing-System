<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Unused = 'unused';
    case Used = 'used';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Unused => __('Unused'),
            self::Used => __('Used invitation'),
            self::Revoked => __('Revoked'),
        };
    }
}
