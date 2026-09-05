<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'code', 'type', 'value', 'usage_limit', 'times_used',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Whether this coupon can be used right now.
     *
     * Four ways to be unusable: switched off, not started, expired, or spent.
     */
    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->usage_limit === null || $this->times_used < $this->usage_limit;
    }

    /**
     * What this coupon takes off a price, never more than the price itself.
     */
    public function discountFor(int $price): int
    {
        $discount = $this->type === DiscountType::Percentage
            ? (int) round($price * $this->value / 100)
            : $this->value;

        return min($discount, $price);
    }

    public function remainingUses(): ?int
    {
        return $this->usage_limit === null ? null : max(0, $this->usage_limit - $this->times_used);
    }
}
