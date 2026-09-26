<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Unused = 'unused';
    case Used = 'used';
    case Revoked = 'revoked';
}
