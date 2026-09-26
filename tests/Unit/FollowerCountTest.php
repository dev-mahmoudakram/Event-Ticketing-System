<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\FollowerCount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FollowerCountTest extends TestCase
{
    /** @return array<string, array{mixed, int}> */
    public static function readableCounts(): array
    {
        return [
            'plain number' => ['30000', 30000],
            'integer' => [1500, 1500],
            'thousands separators' => ['30,000', 30000],
            'spaces' => [' 1 500 ', 1500],
            'k' => ['30k', 30000],
            'capital K' => ['30K', 30000],
            'decimal k' => ['1.2k', 1200],
            'm' => ['1m', 1000000],
            'decimal M' => ['1.5M', 1500000],
            'b' => ['2b', 2000000000],
            'space before suffix' => ['30 k', 30000],
            'arabic digits' => ['٣٠٠٠٠', 30000],
            'arabic digits with ك' => ['٣٠ك', 30000],
            'arabic م' => ['١٫٥م', 1500000],
            'arabic word thousand' => ['30 ألف', 30000],
            'arabic word million' => ['2 مليون', 2000000],
            'zero' => ['0', 0],
        ];
    }

    #[DataProvider('readableCounts')]
    public function test_it_reads_a_follower_count(mixed $input, int $expected): void
    {
        $this->assertSame($expected, FollowerCount::parse($input));
    }

    /** @return array<string, array{mixed}> */
    public static function unreadableCounts(): array
    {
        return [
            'words' => ['lots'],
            'decimal without a suffix' => ['1.5'],
            'unknown suffix' => ['30x'],
            'negative' => ['-5'],
            'two suffixes' => ['1km'],
            'suffix alone' => ['k'],
        ];
    }

    #[DataProvider('unreadableCounts')]
    public function test_it_refuses_what_is_not_a_count(mixed $input): void
    {
        $this->assertNull(FollowerCount::parse($input));
    }

    public function test_blank_is_treated_as_no_answer(): void
    {
        $this->assertTrue(FollowerCount::isBlank(''));
        $this->assertTrue(FollowerCount::isBlank('  '));
        $this->assertTrue(FollowerCount::isBlank(null));
        $this->assertFalse(FollowerCount::isBlank('0'));
    }
}
