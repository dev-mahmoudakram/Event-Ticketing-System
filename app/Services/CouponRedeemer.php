<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiscountCoupon;
use App\Models\Event;
use App\Models\TicketType;

class CouponRedeemer
{
    /**
     * Find a coupon an attendee may use on this event right now.
     *
     * Returns null for a code that does not exist, belongs to another event, is switched off,
     * has not started, has expired, or is spent — the caller does not need to know which,
     * because telling a visitor which of those it was helps nobody but somebody guessing codes.
     */
    public function find(Event $event, ?string $code): ?DiscountCoupon
    {
        if (blank($code)) {
            return null;
        }

        $coupon = $event->discountCoupons()
            ->where('code', strtoupper(trim($code)))
            ->first();

        return $coupon?->isRedeemable() === true ? $coupon : null;
    }

    /**
     * What a ticket of this type costs with the coupon applied.
     *
     * @return array{price: int, discount: int, coupon_id: int|null}
     */
    public function priceFor(TicketType $ticketType, ?DiscountCoupon $coupon): array
    {
        $price = (int) $ticketType->price;

        if ($coupon === null) {
            return ['price' => $price, 'discount' => 0, 'coupon_id' => null];
        }

        return [
            'price' => $price,
            'discount' => $coupon->discountFor($price),
            'coupon_id' => $coupon->id,
        ];
    }

    /**
     * Take one use of a coupon, if one is still left.
     *
     * find() only checked the coupon as it was when it was read; another request may have
     * taken the last use since. The limit is re-checked in the same UPDATE that counts the
     * use, so of two requests racing for the last one, exactly one gets it and the other is
     * told no and charged full price.
     */
    public function claim(DiscountCoupon $coupon): bool
    {
        $claimed = $coupon->newQuery()
            ->whereKey($coupon->getKey())
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('times_used', '<', 'usage_limit'))
            ->increment('times_used');

        return $claimed === 1;
    }
}
