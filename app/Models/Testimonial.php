<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use App\Models\Concerns\ResolvesStoredMedia;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasFactory, ResolvesStoredMedia;

    protected $fillable = [
        'event_id', 'photo_path', 'quote_ar', 'quote_en', 'name_ar', 'name_en', 'title_ar', 'title_en', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quote_ar' => SanitizedRichText::class,
            'quote_en' => SanitizedRichText::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function photoUrl(): ?string
    {
        return $this->storedMediaUrl($this->photo_path);
    }

    protected static function newFactory(): TestimonialFactory
    {
        return TestimonialFactory::new();
    }
}
