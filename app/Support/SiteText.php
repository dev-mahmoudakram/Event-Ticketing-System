<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SiteContent;
use Illuminate\Support\Collection;

/**
 * Reads Creators Hub copy, preferring what an admin has saved and falling back to the
 * wording the page ships with.
 *
 * Everything is loaded in one query on first use and held for the request, so a page reading
 * a hundred fields still costs a single round trip.
 */
class SiteText
{
    /** @var Collection<string, SiteContent>|null */
    private static ?Collection $cache = null;

    /**
     * The value for a field, or the registry default when nothing is stored.
     *
     * Defaults run through __() so a page with no admin overrides still renders in both
     * locales; a stored value is used verbatim, since the admin typed it per locale.
     */
    public static function get(string $section, string $field): string
    {
        $stored = self::stored($section, $field);

        if ($stored !== null && $stored !== '') {
            return $stored;
        }

        $default = SiteContentRegistry::defaultFor($section, $field);

        return $default === null ? '' : __($default);
    }

    /**
     * Resolve a dotted "section.field" reference, as used by the @site Blade directive.
     */
    public static function forExpression(string $reference): string
    {
        [$section, $field] = array_pad(explode('.', $reference, 2), 2, '');

        return self::get($section, $field);
    }

    /**
     * The raw stored value with no fallback — for fields whose whole section hides when the
     * admin has not filled them in.
     */
    public static function stored(string $section, string $field): ?string
    {
        $record = self::all()->get($section.'.'.$field);

        if ($record === null) {
            return null;
        }

        // Images are one file for both locales; text is stored per locale.
        $value = SiteContentRegistry::isImage($section, $field)
            ? $record->value_en
            : (app()->getLocale() === 'ar' ? $record->value_ar : $record->value_en);

        return trim((string) $value) === '' ? null : (string) $value;
    }

    /**
     * A stored image resolved to a URL, or null when none was uploaded.
     */
    public static function image(string $section, string $field): ?string
    {
        $path = self::stored($section, $field);

        return $path === null ? null : (new SiteContent(['value_en' => $path]))->urlFor($path);
    }

    /**
     * @return Collection<string, SiteContent>
     */
    /**
     * The platform's own social accounts, in the canonical order, without the blanks.
     *
     * @return array<string, string>
     */
    public static function socialLinks(): array
    {
        $links = [];

        foreach (SocialPlatforms::keys() as $platform) {
            $links[$platform] = self::stored('contact_details', $platform);
        }

        return SocialPlatforms::filled($links);
    }

    public static function all(): Collection
    {
        return self::$cache ??= SiteContent::all()
            ->keyBy(fn (SiteContent $content) => $content->section.'.'.$content->field_key);
    }

    /**
     * Drops the request-lifetime cache. Tests write content then immediately render.
     */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
