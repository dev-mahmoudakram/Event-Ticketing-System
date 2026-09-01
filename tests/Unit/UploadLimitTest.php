<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\UploadLimit;
use PHPUnit\Framework\TestCase;

class UploadLimitTest extends TestCase
{
    public function test_it_reports_a_positive_limit(): void
    {
        $this->assertGreaterThan(0, UploadLimit::kilobytes());
    }

    public function test_it_never_exceeds_what_php_accepts(): void
    {
        $toBytes = function (string $value): int {
            $unit = strtolower(substr(trim($value), -1));
            $number = (int) $value;

            return match ($unit) {
                'g' => $number * 1024 * 1024 * 1024,
                'm' => $number * 1024 * 1024,
                'k' => $number * 1024,
                default => (int) $value,
            };
        };

        $phpCeiling = min(array_filter([
            $toBytes((string) ini_get('upload_max_filesize')),
            $toBytes((string) ini_get('post_max_size')),
        ]));

        // Validation must never promise more than PHP will let through, otherwise an
        // oversized file dies at the PHP layer with an opaque "failed to upload".
        $this->assertLessThanOrEqual($phpCeiling, UploadLimit::kilobytes() * 1024);
    }

    public function test_it_labels_the_limit_for_humans(): void
    {
        $this->assertMatchesRegularExpression('/^[\d.]+ (KB|MB)$/', UploadLimit::label());
    }
}
