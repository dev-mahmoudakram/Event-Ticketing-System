<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case PaymentPending = 'payment_pending';
    case Paid = 'paid';
    case TicketIssued = 'ticket_issued';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';

    /**
     * The name a person reads, rather than the value the database stores.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending review'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::PaymentPending => __('Awaiting payment'),
            self::Paid => __('Paid'),
            self::TicketIssued => __('Ticket issued'),
            self::CheckedIn => __('Checked in'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
