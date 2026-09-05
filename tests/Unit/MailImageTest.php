<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MailImage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MailImageTest extends TestCase
{
    /**
     * Stands in for the mail message, recording what it was asked to attach.
     */
    private function message(): object
    {
        return new class
        {
            public array $embedded = [];

            public function embedData(string $data, string $name, string $mime): string
            {
                $this->embedded[] = $name;

                return 'cid:'.$name;
            }
        };
    }

    public function test_a_picture_this_app_serves_is_attached(): void
    {
        $message = $this->message();

        $result = MailImage::embed($message, '/images/creators-hub/mark.png');

        $this->assertSame('cid:mark.png', $result);
        $this->assertSame(['mark.png'], $message->embedded);
    }

    public function test_a_remote_picture_is_left_as_a_link(): void
    {
        $message = $this->message();

        $this->assertSame(
            'https://cdn.example.com/logo.png',
            MailImage::embed($message, 'https://cdn.example.com/logo.png'),
        );
        $this->assertSame([], $message->embedded);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function escapingPaths(): array
    {
        return [
            ['/../.env'],
            ['/images/../../.env'],
            ['/%2e%2e/%2e%2e/.env'],
            ['/../composer.json'],
        ];
    }

    #[DataProvider('escapingPaths')]
    public function test_a_path_climbing_out_of_public_reads_nothing(string $path): void
    {
        $message = $this->message();

        MailImage::embed($message, $path);

        $this->assertSame([], $message->embedded, $path.' should never be read.');
    }

    public function test_a_file_that_is_not_a_picture_is_not_attached(): void
    {
        $message = $this->message();

        MailImage::embed($message, '/index.php');

        $this->assertSame([], $message->embedded);
    }

    public function test_nothing_happens_outside_a_mail_view(): void
    {
        $this->assertSame('/images/creators-hub/mark.png', MailImage::embed(null, '/images/creators-hub/mark.png'));
        $this->assertNull(MailImage::embed($this->message(), null));
    }
}
