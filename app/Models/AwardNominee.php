<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStoredMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardNominee extends Model
{
    use HasFactory;
    use ResolvesStoredMedia;

    protected $fillable = [
        'award_id', 'name_ar', 'name_en', 'bio_ar', 'bio_en', 'image_path', 'sort_order',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(AwardVote::class);
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function bio(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->bio_ar : $this->bio_en;
    }

    public function imageUrl(): ?string
    {
        return $this->storedMediaUrl($this->image_path);
    }
}
