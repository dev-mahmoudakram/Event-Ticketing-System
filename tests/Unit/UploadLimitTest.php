<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\UploadLimit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadLimitTest extends TestCase
{
    public function test_it_reports_a_positive_server_limit(): void
    {
        $this->assertGreaterThan(0, UploadLimit::serverKilobytes());
    }

    public function test_the_effective_limit_never_exceeds_what_php_accepts(): void
    {
        // Validation must never promise more than PHP will let through, or an oversized file
        // dies at the PHP layer with an opaque "failed to upload" instead of a size error.
        $this->assertLessThanOrEqual(
            UploadLimit::serverKilobytes(),
            UploadLimit::effectiveKilobytes(25 * 1024),
        );
    }

    public function test_the_effective_limit_honours_a_lower_application_ceiling(): void
    {
        $this->assertSame(1, UploadLimit::effectiveKilobytes(1));
    }

    public function test_it_reports_whether_the_server_is_the_binding_constraint(): void
    {
        $server = UploadLimit::serverKilobytes();

        $this->assertTrue(UploadLimit::isServerConstrained($server + 1));
        $this->assertFalse(UploadLimit::isServerConstrained($server));
        $this->assertFalse(UploadLimit::isServerConstrained(1));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function labels(): array
    {
        return [
            '25 MB' => [25 * 1024, '25 MB'],
            '2 MB' => [2 * 1024, '2 MB'],
            'half a MB' => [512, '512 KB'],
            'fractional MB' => [2560, '2.5 MB'],
        ];
    }

    #[DataProvider('labels')]
    public function test_it_labels_sizes_for_humans(int $kilobytes, string $expected): void
    {
        $this->assertSame($expected, UploadLimit::label($kilobytes));
    }
}
