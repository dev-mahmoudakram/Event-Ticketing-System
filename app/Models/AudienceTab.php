<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AudienceTabFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudienceTab extends Model
{
    /** @use HasFactory<AudienceTabFactory> */
    use HasFactory;

    protected $fillable = [
        'label_ar', 'label_en', 'lede_ar', 'lede_en', 'cta_ar', 'cta_en', 'sort_order',
    ];

    /**
     * @return HasMany<AudienceCard, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(AudienceCard::class)->orderBy('sort_order')->orderBy('id');
    }

    public function label(): string
    {
        return (string) (app()->getLocale() === 'ar' ? $this->label_ar : $this->label_en);
    }

    public function lede(): string
    {
        return (string) (app()->getLocale() === 'ar' ? $this->lede_ar : $this->lede_en);
    }

    public function cta(): string
    {
        return (string) (app()->getLocale() === 'ar' ? $this->cta_ar : $this->cta_en);
    }

    /**
     * A tab with no label would render as an unclickable gap in the switch, so the section
     * skips it rather than showing a blank button.
     */
    public function isComplete(): bool
    {
        return trim($this->label()) !== '';
    }
}
