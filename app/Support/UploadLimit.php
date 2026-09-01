<?php

declare(strict_types=1);

namespace App\Support;

class UploadLimit
{
    /**
     * The largest upload PHP will actually accept, in kilobytes.
     *
     * PHP enforces upload_max_filesize and post_max_size before a request ever reaches
     * validation, so the smaller of the two is the real ceiling. Validating against it means a
     * too-large file is reported as a size problem rather than the opaque "failed to upload".
     */
    public static function kilobytes(): int
    {
        $limits = array_filter([
            self::toBytes((string) ini_get('upload_max_filesize')),
            self::toBytes((string) ini_get('post_max_size')),
        ]);

        // A value of 0 means "unlimited" for post_max_size, and both being absent would be odd;
        // fall back to a conservative 8 MB so the rule is always a real number.
        $bytes = $limits === [] ? 8 * 1024 * 1024 : min($limits);

        return max(1, (int) floor($bytes / 1024));
    }

    /**
     * Human-readable form of the limit, for hints and error messages.
     */
    public static function label(): string
    {
        $kilobytes = self::kilobytes();

        return $kilobytes >= 1024
            ? round($kilobytes / 1024, 1).' MB'
            : $kilobytes.' KB';
    }

    /**
     * Convert a php.ini shorthand size ("2M", "512K", "1G") into bytes.
     */
    private static function toBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
