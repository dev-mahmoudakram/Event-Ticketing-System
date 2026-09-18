<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\SanitizedRichText;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'name_ar', 'name_en', 'description_ar', 'description_en',
        'price', 'original_price', 'currency', 'workshop_slot_count', 'sort_order',
        'is_active', 'is_popular', 'popular_label_ar', 'popular_label_en',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'description_ar' => SanitizedRichText::class,
        'description_en' => SanitizedRichText::class,
    ];

    /**
     * Whether a sale price is actually in effect — an original price is only meaningful when
     * it is higher than what is being charged now.
     */
    public function isOnSale(): bool
    {
        return $this->original_price !== null && $this->original_price > $this->price;
    }

    /**
     * The badge text for the popular card, in the current locale — an admin-supplied label if
     * one was set, otherwise the default wording.
     */
    public function popularLabel(): string
    {
        $label = app()->getLocale() === 'ar' ? $this->popular_label_ar : $this->popular_label_en;

        return trim((string) $label) !== '' ? $label : __('Most Popular');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(TicketTypeFeature::class)->orderBy('sort_order');
    }

    protected static function newFactory(): TicketTypeFactory
    {
        return TicketTypeFactory::new();
    }
}
