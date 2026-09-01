<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\UploadLimit;
use Illuminate\Console\Command;

class ShowUploadLimits extends Command
{
    protected $signature = 'media:limits';

    protected $description = 'Show the upload ceilings this application and PHP will accept';

    public function handle(): int
    {
        $video = (int) config('media.max_video_kb');
        $image = (int) config('media.max_image_kb');

        $this->table(['Setting', 'Value'], [
            ['php upload_max_filesize', ini_get('upload_max_filesize')],
            ['php post_max_size', ini_get('post_max_size')],
            ['php max_input_time', ini_get('max_input_time')],
            ['loaded php.ini', php_ini_loaded_file() ?: '(none)'],
            ['scanned .ini files', php_ini_scanned_files() ?: '(none)'],
            ['---', '---'],
            ['app video ceiling', UploadLimit::label($video)],
            ['app image ceiling', UploadLimit::label($image)],
            ['effective video limit', UploadLimit::label(UploadLimit::effectiveKilobytes($video))],
        ]);

        if (UploadLimit::isServerConstrained($video)) {
            $this->warn(sprintf(
                'PHP caps uploads at %s, below the %s this app allows, so larger videos are rejected before validation runs.',
                UploadLimit::label(UploadLimit::serverKilobytes()),
                UploadLimit::label($video),
            ));
            $this->line('Raise upload_max_filesize and post_max_size (public/.user.ini, or Plesk > PHP Settings).');

            return self::FAILURE;
        }

        $this->info('PHP accepts the full '.UploadLimit::label($video).' this app allows.');

        return self::SUCCESS;
    }
}
