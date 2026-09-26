<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use App\Models\Concerns\HasOrderedSpeakers;
use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AgendaItem extends Model
{
    use HasFactory, HasOrderedSpeakers;

    protected $fillable = [
        'event_id', 'session_type_id', 'location_id', 'day_date', 'start_time', 'end_time',
        'title_ar', 'title_en', 'description_ar', 'description_en', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'day_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'description_ar' => SanitizedRichText::class,
            'description_en' => SanitizedRichText::class,
        ];
    }

    public function title(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function description(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sessionType(): BelongsTo
    {
        return $this->belongsTo(SessionType::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    protected static function newFactory(): AgendaItemFactory
    {
        return AgendaItemFactory::new();
    }
}
