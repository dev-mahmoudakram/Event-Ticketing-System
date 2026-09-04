<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SiteSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SiteContent extends Model
{
    protected $fillable = ['section', 'field_key', 'value_ar', 'value_en'];

    protected $casts = ['section' => SiteSection::class];

    /**
     * Every stored value for a section, keyed by field, resolved to the current locale.
     *
     * Blank values are dropped so a view can ask "is anything filled in here?" with a simple
     * isEmpty(), and half-configured sections can hide themselves rather than render gaps.
     *
     * @return Collection<string, string>
     */
    public static function valuesFor(SiteSection $section): Collection
    {
        $column = app()->getLocale() === 'ar' ? 'value_ar' : 'value_en';

        return static::query()
            ->where('section', $section)
            ->get()
            ->mapWithKeys(fn (SiteContent $content) => [$content->field_key => (string) $content->{$column}])
            ->filter(fn (string $value) => trim($value) !== '');
    }
}
