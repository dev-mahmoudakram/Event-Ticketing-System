<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStoredMedia;
use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sponsor extends Model
{
    use HasFactory, ResolvesStoredMedia;

    protected $fillable = [
        'event_id', 'name_ar', 'name_en', 'logo_path', 'sponsor_tier_id', 'website_url', 'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SponsorTier::class, 'sponsor_tier_id');
    }

    public function logoUrl(): ?string
    {
        return $this->storedMediaUrl($this->logo_path);
    }

    protected static function newFactory(): SponsorFactory
    {
        return SponsorFactory::new();
    }
}
