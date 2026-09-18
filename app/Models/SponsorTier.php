<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SponsorTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SponsorTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'name_ar', 'name_en', 'sort_order',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class)->orderBy('sort_order');
    }

    protected static function newFactory(): SponsorTierFactory
    {
        return SponsorTierFactory::new();
    }
}
