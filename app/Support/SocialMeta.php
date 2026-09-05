<?php

declare(strict_types=1);

namespace App\Support;

class SocialMeta
{
    /**
     * Turn a stored media path into the absolute URL social networks require.
     *
     * Uploads resolve to root-relative paths so they survive whatever host the site is served
     * from, but a crawler only accepts an absolute URL, so they are expanded here.
     */
    public static function absoluteUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    /**
     * Read the size and type of an image this application serves itself.
     *
     * Twitter and Facebook render a card faster when they are told this up front. A file this
     * app does not serve, or one that cannot be read, simply goes without.
     *
     * @return array{width: int, height: int, mime: string}|null
     */
    public static function imageDetails(?string $path): ?array
    {
        if (blank($path)) {
            return null;
        }

        $relative = parse_url($path, PHP_URL_PATH);

        if (! is_string($relative) || $relative === '') {
            return null;
        }

        $file = public_path(ltrim($relative, '/'));

        if (! is_file($file)) {
            return null;
        }

        $size = @getimagesize($file);

        if ($size === false) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1], 'mime' => $size['mime'] ?? 'image/png'];
    }
}
