<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InfluencerCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerCategory extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'name_ar', 'name_en', 'sort_order'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function newFactory(): InfluencerCategoryFactory
    {
        return InfluencerCategoryFactory::new();
    }
}
