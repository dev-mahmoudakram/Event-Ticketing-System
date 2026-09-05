<?php

declare(strict_types=1);

namespace App\Support;

class MailImage
{
    /**
     * Attach an image this application serves to the message itself.
     *
     * Mail clients routinely block remote images, which would leave a ticket with no logos on
     * it. A file we serve ourselves is embedded instead, so it always draws; anything hosted
     * elsewhere is left as a plain URL.
     *
     * @param  object|null  $message  The mail message, available inside mail views only.
     */
    public static function embed(?object $message, ?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if ($message === null || ! method_exists($message, 'embedData')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $url;
        }

        $file = public_path(ltrim($path, '/'));

        if (! is_file($file)) {
            return $url;
        }

        return $message->embedData(
            (string) file_get_contents($file),
            basename($file),
            mime_content_type($file) ?: 'image/png',
        );
    }
}
