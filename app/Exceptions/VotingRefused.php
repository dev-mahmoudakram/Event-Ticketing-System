<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A vote or code request the rules turn down. The message is already translated and safe to
 * show the visitor as-is; `field` names the form field it belongs under ('email' or 'code').
 */
final class VotingRefused extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'email')
    {
        parent::__construct($message);
    }

    /**
     * A refusal about the code the visitor typed, rather than about their email.
     */
    public static function forCode(string $message): self
    {
        return new self($message, 'code');
    }
}
