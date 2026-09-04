<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStoredMedia;
use App\Support\SiteText;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use ResolvesStoredMedia;

    protected $fillable = [
        'image_path', 'headline_ar', 'headline_en', 'body_ar', 'body_en', 'sort_order',
    ];

    public function imageUrl(): ?string
    {
        return $this->storedMediaUrl($this->image_path);
    }

    /**
     * The slide's own headline, falling back to the hero copy in site content so a slide that
     * only changes the image still reads correctly.
     */
    public function headline(): string
    {
        $own = app()->getLocale() === 'ar' ? $this->headline_ar : $this->headline_en;

        return trim((string) $own) !== '' ? (string) $own : SiteText::get('hero', 'headline');
    }

    public function body(): string
    {
        $own = app()->getLocale() === 'ar' ? $this->body_ar : $this->body_en;

        return trim((string) $own) !== '' ? (string) $own : SiteText::get('hero', 'body');
    }
}
