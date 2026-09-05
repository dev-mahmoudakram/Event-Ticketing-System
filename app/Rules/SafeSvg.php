<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Reject an SVG that can run script.
 *
 * SVG is markup, not pixels: opened directly at its own URL it executes whatever it contains,
 * on this application's domain. Uploads are admin-only, so this guards against a file picked up
 * from elsewhere rather than against the admin, but the file is served to visitors afterwards.
 * Anything that is not an SVG passes straight through.
 */
class SafeSvg implements ValidationRule
{
    /** @var list<string> Markup that has no business in a logo or an illustration. */
    private const FORBIDDEN = [
        '<script', '<foreignobject', '<iframe', '<embed', '<object',
        'javascript:', 'data:text/html',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        if (! $this->isSvg($value)) {
            return;
        }

        $markup = (string) file_get_contents($value->getRealPath());
        $haystack = strtolower($markup);

        foreach (self::FORBIDDEN as $needle) {
            if (str_contains($haystack, $needle)) {
                $fail(__('This SVG contains scripting, so it was not uploaded. Export it again without scripts, or upload a PNG.'))->translate();

                return;
            }
        }

        // Event handler attributes: onload, onclick, onmouseover and the rest.
        if (preg_match('/\son[a-z]+\s*=/i', $markup) === 1) {
            $fail(__('This SVG contains scripting, so it was not uploaded. Export it again without scripts, or upload a PNG.'))->translate();
        }
    }

    private function isSvg(UploadedFile $file): bool
    {
        return strtolower((string) $file->getClientOriginalExtension()) === 'svg'
            || $file->getMimeType() === 'image/svg+xml';
    }
}
