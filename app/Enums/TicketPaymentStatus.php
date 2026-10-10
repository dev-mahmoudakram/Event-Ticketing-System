<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What became of one payment link. Only Pending can still change.
 */
enum TicketPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Awaiting payment'),
            self::Paid => __('Paid'),
            self::Failed => __('Payment failed'),
            self::Expired => __('Link expired'),
            self::Abandoned => __('Replaced by a newer link'),
        };
    }

    /**
     * The local status for a Kashier session status. Null means the session is still open.
     *
     * We never authorise-then-capture, so AUTHORIZED is still in progress. A session that was
     * voided, reversed or refunded before we saw it paid is treated as failed: no ticket.
     */
    public static function fromKashier(string $sessionStatus): ?self
    {
        return match (strtoupper($sessionStatus)) {
            'PAID' => self::Paid,
            'FAILED', 'REJECTED', 'VOIDED', 'REVERSED', 'REFUNDED' => self::Failed,
            'EXPIRED' => self::Expired,
            'ABANDONED' => self::Abandoned,
            default => null,
        };
    }
}
