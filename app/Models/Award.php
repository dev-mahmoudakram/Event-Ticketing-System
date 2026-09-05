<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStoredMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Award extends Model
{
    use HasFactory;
    use ResolvesStoredMedia;

    protected $fillable = [
        'event_id', 'category_ar', 'category_en',
        'nominee_name_ar', 'nominee_name_en', 'nominee_bio_ar', 'nominee_bio_en',
        'image_path', 'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(AwardVote::class);
    }

    public function category(): string
    {
        return app()->getLocale() === 'ar' ? $this->category_ar : $this->category_en;
    }

    public function nomineeName(): string
    {
        return app()->getLocale() === 'ar' ? $this->nominee_name_ar : $this->nominee_name_en;
    }

    public function nomineeBio(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->nominee_bio_ar : $this->nominee_bio_en;
    }

    public function imageUrl(): ?string
    {
        return $this->storedMediaUrl($this->image_path);
    }
}
