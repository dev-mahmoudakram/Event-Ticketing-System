<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Reads a follower count the way people write one: "30000", "30,000", "30k", "1.2m", or in
 * Arabic "٣٠ك", "١٫٥م", "30 ألف". The browser shows the same reading as a hint while typing
 * (resources/js/follower-count.js), so the two must agree.
 */
class FollowerCount
{
    /** @var array<string, int> */
    private const MULTIPLIERS = [
        'k' => 1_000,
        'ك' => 1_000,
        'ألف' => 1_000,
        'الف' => 1_000,
        'm' => 1_000_000,
        'م' => 1_000_000,
        'مليون' => 1_000_000,
        'b' => 1_000_000_000,
        'مليار' => 1_000_000_000,
    ];

    public static function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * The count as a whole number, or null when the text is not a count at all.
     */
    public static function parse(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $text = strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.', '٬' => '', ',' => '', '_' => '',
        ]);
        $text = mb_strtolower((string) preg_replace('/\s+/u', '', $text));

        $suffixes = implode('|', array_map(fn (string $suffix) => preg_quote($suffix, '/'), array_keys(self::MULTIPLIERS)));

        if (preg_match('/^(\d+(?:\.\d+)?)('.$suffixes.')?$/u', $text, $matches) !== 1) {
            return null;
        }

        $suffix = $matches[2] ?? '';

        // "1.5" on its own is not a whole number of people; "1.5k" is.
        if ($suffix === '' && str_contains($matches[1], '.')) {
            return null;
        }

        return (int) round((float) $matches[1] * (self::MULTIPLIERS[$suffix] ?? 1));
    }
}
