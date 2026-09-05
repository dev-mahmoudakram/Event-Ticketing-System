<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'speaker_id', 'slug', 'name_ar', 'name_en',
        'description_ar', 'description_en', 'capacity', 'sort_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Speaker::class);
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

        return max(0, (int) $this->capacity - $this->bookings()->count());
    }

    public function isFull(): bool
    {
        return $this->remainingCapacity() === 0;
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class)->orderBy('day_date')->orderBy('start_time');
    }

    protected static function newFactory(): WorkshopFactory
    {
        return WorkshopFactory::new();
    }
}
