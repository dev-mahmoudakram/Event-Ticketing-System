<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which Kashier environment payments go to. Each has its own keys and its own API host.
 */
enum PaymentMode: string
{
    case Test = 'test';
    case Live = 'live';

    public function label(): string
    {
        return match ($this) {
            self::Test => __('Test (no real money)'),
            self::Live => __('Live'),
        };
    }

    public function apiBaseUrl(): string
    {
        return match ($this) {
            self::Test => 'https://test-api.kashier.io',
            self::Live => 'https://api.kashier.io',
        };
    }
}
