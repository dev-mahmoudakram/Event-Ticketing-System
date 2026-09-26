<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Mail\TicketIssued;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The one place a ticket becomes a real, admittable ticket: its QR secret, its workshop key
 * and the email that delivers them. Paid tickets and invitations both come through here.
 */
class TicketIssuer
{
    public function __construct(
        private readonly WorkshopBooker $workshopBooker,
        private readonly TicketQrCode $qrCode,
    ) {}

    /**
     * The attendee-facing reference, e.g. CCS2026-000042.
     */
    public function referenceFor(Ticket $ticket): string
    {
        $prefix = strtoupper(str_replace('-', '', $ticket->event->slug));

        return $prefix.'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Issue the ticket if it is still in $from, and report whether this call did it.
     *
     * The status check and the change are one conditional UPDATE, so two requests that both
     * loaded the ticket before either issued it (a double click, an email scanner opening the
     * link as well as the attendee) can't each write a different QR secret — only one wins.
     */
    public function issue(Ticket $ticket, string $paymentMethod, TicketStatus $from): bool
    {
        return DB::transaction(function () use ($ticket, $paymentMethod, $from): bool {
            $issued = Ticket::query()
                ->whereKey($ticket->id)
                ->where('status', $from)
                ->update([
                    'ticket_id' => Str::random(40),
                    'is_paid' => true,
                    'payment_method' => $paymentMethod,
                    'status' => TicketStatus::TicketIssued,
                ]);

            if ($issued !== 1) {
                return false;
            }

            $ticket->refresh()->load('ticketType');

            // Only tiers that include workshops get a booking key.
            $this->workshopBooker->issueKeyFor($ticket);
            $ticket->refresh();

            return true;
        });
    }

    /**
     * Email the issued ticket with its QR code attached.
     *
     * @throws RuntimeException when the ticket has not been issued
     */
    public function send(Ticket $ticket): void
    {
        $qrImage = $this->qrCode->pngFor($ticket);

        if ($qrImage === null) {
            throw new RuntimeException('Ticket '.$ticket->id.' has not been issued, so it has no QR code to send.');
        }

        Mail::to($ticket->email)->send(new TicketIssued($ticket, $qrImage));
    }
}
