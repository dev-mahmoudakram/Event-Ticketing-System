<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Ceilings
    |--------------------------------------------------------------------------
    |
    | What this application is willing to accept, in kilobytes. PHP's own
    | upload_max_filesize / post_max_size still apply on top: the effective limit
    | is whichever is smaller, since PHP discards an oversized upload before the
    | request ever reaches validation.
    |
    */

    'max_video_kb' => (int) env('MEDIA_MAX_VIDEO_KB', 25 * 1024),
    'max_image_kb' => (int) env('MEDIA_MAX_IMAGE_KB', 4 * 1024),

];
