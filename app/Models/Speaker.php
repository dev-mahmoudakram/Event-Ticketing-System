<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use App\Models\Concerns\ResolvesStoredMedia;
use Database\Factories\SpeakerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Speaker extends Model
{
    use HasFactory, ResolvesStoredMedia;

    protected $fillable = [
        'event_id', 'name_ar', 'name_en', 'title_ar', 'title_en',
        'bio_ar', 'bio_en', 'photo_path', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bio_ar' => SanitizedRichText::class,
            'bio_en' => SanitizedRichText::class,
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

    protected static function newFactory(): SpeakerFactory
    {
        return SpeakerFactory::new();
    }
}
