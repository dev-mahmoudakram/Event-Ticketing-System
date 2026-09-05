<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStoredMedia;
use Database\Factories\AudienceCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceCard extends Model
{
    /** @use HasFactory<AudienceCardFactory> */
    use HasFactory, ResolvesStoredMedia;

    protected $fillable = [
        'audience_tab_id', 'title_ar', 'title_en', 'body_ar', 'body_en', 'image_path', 'sort_order',
    ];

    /**
     * @return BelongsTo<AudienceTab, $this>
     */
    public function tab(): BelongsTo
    {
        return $this->belongsTo(AudienceTab::class, 'audience_tab_id');
    }

    public function title(): string
    {
        return (string) (app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en);
    }

    public function body(): string
    {
        return (string) (app()->getLocale() === 'ar' ? $this->body_ar : $this->body_en);
    }

    public function imageUrl(): ?string
    {
        return $this->storedMediaUrl($this->image_path);
    }
}
