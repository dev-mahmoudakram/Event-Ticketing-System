<?php

declare(strict_types=1);

namespace App\Support;

class UploadLimit
{
    /**
     * The largest upload PHP itself will accept, in kilobytes.
     *
     * PHP enforces upload_max_filesize and post_max_size before a request reaches validation,
     * so the smaller of the two is a hard ceiling no application setting can lift.
     */
    public static function serverKilobytes(): int
    {
        $limits = array_filter([
            self::toBytes((string) ini_get('upload_max_filesize')),
            self::toBytes((string) ini_get('post_max_size')),
        ]);

        // post_max_size of 0 means "unlimited"; both missing would be odd, so fall back to a
        // conservative 8 MB and let the intended limit do the rest of the work.
        $bytes = $limits === [] ? 8 * 1024 * 1024 : min($limits);

        return max(1, (int) floor($bytes / 1024));
    }

    /**
     * What to actually validate against: the application's intended ceiling, lowered to
     * whatever PHP will really let through.
     */
    public static function effectiveKilobytes(int $intendedKilobytes): int
    {
        return min($intendedKilobytes, self::serverKilobytes());
    }

    /**
     * Whether the server, rather than this application, is the binding constraint — which
     * means the fix is a php.ini change and not a config one.
     */
    public static function isServerConstrained(int $intendedKilobytes): bool
    {
        return self::serverKilobytes() < $intendedKilobytes;
    }

    /**
     * Human-readable form of a kilobyte figure, for hints and error messages.
     */
    public static function label(int $kilobytes): string
    {
        return $kilobytes >= 1024
            ? rtrim(rtrim(number_format($kilobytes / 1024, 1), '0'), '.').' MB'
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
