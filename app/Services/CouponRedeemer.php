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
     * Count one use against a coupon.
     *
     * Incremented atomically in the database rather than read-modify-written in PHP, so two
     * people redeeming the last use of a coupon at the same moment cannot both succeed.
     */
    public function recordUse(DiscountCoupon $coupon): void
    {
        $coupon->newQuery()->whereKey($coupon->getKey())->increment('times_used');
    }
}
