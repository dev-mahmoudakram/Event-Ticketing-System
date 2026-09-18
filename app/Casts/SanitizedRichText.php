<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\RichText;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Sanitizes a rich-text attribute on write, so no controller can forget to purify it.
 *
 * CKEditor constrains what the editor UI can produce, but a form post is not the editor —
 * it can be crafted by hand with arbitrary HTML. Putting the purifier in the cast rather than
 * in each controller means every write path (mass assignment, ->update(), a factory, a
 * seeder, tinker) goes through the same sanitizer, on the model itself, where it cannot be
 * bypassed by a new controller action later forgetting to call clean().
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class SanitizedRichText implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return RichText::clean($value === null ? null : (string) $value);
    }
}
