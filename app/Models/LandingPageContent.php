<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LandingPageSection;
use App\Models\Concerns\ResolvesStoredMedia;
use Database\Factories\LandingPageContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageContent extends Model
{
    use HasFactory;
    use ResolvesStoredMedia;

    protected $table = 'landing_page_content';

    protected $fillable = [
        'event_id', 'section', 'field_key', 'value_ar', 'value_en',
    ];

    protected $casts = [
        'section' => LandingPageSection::class,
    ];

    /**
     * The URL of a row that holds an uploaded file rather than translated text.
     *
     * These rows keep the same path in both languages, since a file is not translated.
     */
    public function mediaUrl(): ?string
    {
        return $this->storedMediaUrl($this->value_en);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    protected static function newFactory(): LandingPageContentFactory
    {
        return LandingPageContentFactory::new();
    }
}
