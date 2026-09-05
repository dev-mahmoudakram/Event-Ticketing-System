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

        $file = self::publicFile($url);

        if ($file === null) {
            return $url;
        }

        return $message->embedData(
            (string) file_get_contents($file),
            basename($file),
            mime_content_type($file) ?: 'image/png',
        );
    }

    /**
     * Resolve a URL to a picture inside public/, or nothing.
     *
     * This reads a file off disk from a URL, so it stays inside the public directory and
     * accepts only image extensions: a path that climbs out of it, or points at something
     * that is not a picture, is left as a plain URL instead.
     */
    private static function publicFile(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        // Decode before looking for climbing segments, or %2e%2e walks straight past the check.
        $path = rawurldecode($path);

        if (str_contains($path, '..')) {
            return null;
        }

        $file = realpath(public_path(ltrim($path, '/')));
        $root = realpath(public_path());

        if ($file === false || $root === false || ! is_file($file)) {
            return null;
        }

        if (! str_starts_with($file.DIRECTORY_SEPARATOR, $root.DIRECTORY_SEPARATOR)) {
            return null;
        }

        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];

        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed, true) ? $file : null;
    }
}
