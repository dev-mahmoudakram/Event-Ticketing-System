<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use App\Models\Concerns\HasOrderedSpeakers;
use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    use HasFactory, HasOrderedSpeakers;

    protected $fillable = [
        'event_id', 'location_id', 'slug', 'name_ar', 'name_en', 'day_date', 'start_time', 'end_time',
        'description_ar', 'description_en', 'capacity', 'sort_order',
    ];

    /**
     * @return array<string, string>
     */
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function description(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    /**
     * Has a day and times, so it can appear on the agenda.
     */
    public function isScheduled(): bool
    {
        return $this->day_date !== null && $this->start_time !== null && $this->end_time !== null;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(WorkshopBooking::class);
    }

    /**
     * Places left, counted from the bookings themselves rather than a stored tally, so the
     * number cannot drift away from reality. A capacity of zero means unlimited.
     */
    public function remainingCapacity(): ?int
    {
        if ((int) $this->capacity === 0) {
            return null;
        }

        // Uses the eager-loaded count when there is one, so a page of cards doesn't query per card.
        return max(0, (int) $this->capacity - ($this->bookings_count ?? $this->bookings()->count()));
    }

    public function isFull(): bool
    {
        return $this->remainingCapacity() === 0;
    }

    protected static function newFactory(): WorkshopFactory
    {
        return WorkshopFactory::new();
    }
}
