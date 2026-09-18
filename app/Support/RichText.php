<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The one place rich-text CMS content is sanitized.
 *
 * CKEditor is configured client-side with only a handful of formatting plugins, but that
 * constrains the UI, not the request — a form post can be crafted by hand with any HTML at
 * all, bypassing the editor entirely. This is the actual security boundary: every rich-text
 * field is purified here, on every write, against the strict 'cms' profile in
 * config/purifier.php, before it is stored. Nothing downstream should call clean() directly,
 * so the profile name and the null-handling below stay in exactly one place.
 */
class RichText
{
    /**
     * Sanitize a rich-text value for storage. A null or empty value passes through unchanged
     * rather than becoming an empty string, so "never set" and "cleared to nothing" remain
     * distinguishable where that matters.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        return clean($html, 'cms');
    }
}
