<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\UploadLimit;
use Tests\TestCase;

class ShowUploadLimitsCommandTest extends TestCase
{
    public function test_it_reports_the_configured_ceilings(): void
    {
        config(['media.max_video_kb' => 25 * 1024]);

        $this->artisan('media:limits')
            ->expectsOutputToContain('25 MB')
            ->expectsOutputToContain(ini_get('upload_max_filesize'));
    }

    public function test_it_fails_when_php_is_the_binding_constraint(): void
    {
        // A ceiling PHP cannot possibly satisfy, so the command must flag it.
        config(['media.max_video_kb' => (UploadLimit::serverKilobytes() + 1024)]);

        $this->artisan('media:limits')->assertFailed();
    }

    public function test_it_succeeds_when_php_can_satisfy_the_ceiling(): void
    {
        config(['media.max_video_kb' => 1]);

        $this->artisan('media:limits')->assertSuccessful();
    }
}
