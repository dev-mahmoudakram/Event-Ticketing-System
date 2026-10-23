<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketPaymentStatus;
use App\Enums\TicketStatus;
use App\Mail\TicketRequestApproved;
use App\Models\Ticket;
use App\Models\TicketPayment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Emails an approved attendee a Kashier link to pay for their ticket, on approval and on every
 * resend. A ticket with nothing left to pay is issued straight away instead.
 *
 * Callers wrap this in a transaction with the ticket's status change: if Kashier or the mail
 * server fails, the exception rolls the payment row back with it, and nothing was sent.
 */
class PaymentLinkSender
{
    /**
     * How long a payment link stays open. A resend from the dashboard opens a new one.
     */
    public const LINK_LIFETIME_HOURS = 24;

    public function __construct(
        private readonly KashierGateway $gateway,
        private readonly TicketIssuer $issuer,
    ) {}

    /**
     * Send the ticket a fresh payment link, closing any earlier one first.
     *
     * The ticket must already be awaiting payment.
     *
     * @throws PaymentGatewayException when Kashier is not set up, unreachable or refuses
     */
    public function send(Ticket $ticket, ?User $sentBy = null): void
    {
        $ticket->loadMissing(['event', 'ticketType']);

        if ($ticket->amountDue() === 0) {
            $this->issueFreeTicket($ticket);

            return;
        }

        $this->abandonOpenLinks($ticket);

        $expiresAt = now()->addHours(self::LINK_LIFETIME_HOURS);
        $order = $this->issuer->referenceFor($ticket).'-'.Str::upper(Str::random(6));
        $eventName = app()->getLocale() === 'ar' ? $ticket->event->name_ar : $ticket->event->name_en;

        $session = $this->gateway->createSession([
            'order' => $order,
            'amount' => $ticket->amountDue(),
            'currency' => $ticket->ticketType->currency ?: 'EGP',
            'expires_at' => $expiresAt,
            'email' => $ticket->email,
            'reference' => (string) $ticket->id,
            'description' => $eventName.' — '.$ticket->ticket_number,
            'locale' => app()->getLocale(),
        ]);

        $payment = $ticket->payments()->create([
            'sent_by' => $sentBy?->id,
            'merchant_order_id' => $order,
            'session_id' => $session['session_id'],
            'session_url' => $session['session_url'],
            'amount' => $ticket->amountDue(),
            'currency' => $ticket->ticketType->currency ?: 'EGP',
            'status' => TicketPaymentStatus::Pending,
            'expires_at' => $expiresAt,
        ]);

        Mail::to($ticket->email)->send(new TicketRequestApproved($ticket, $payment->session_url, $expiresAt));
    }

    /**
     * Close every link still open on this ticket, so only the newest one can be paid.
     *
     * Closing at Kashier is best effort: a session that fails to close still expires on its
     * own, and a payment that lands on it anyway is still recorded (TicketPaymentConfirmer).
     */
    private function abandonOpenLinks(Ticket $ticket): void
    {
        $ticket->payments()
            ->where('status', TicketPaymentStatus::Pending)
            ->get()
            ->each(function (TicketPayment $payment): void {
                if ($payment->session_id !== null) {
                    try {
                        $this->gateway->abandonSession($payment->session_id);
                    } catch (Throwable $exception) {
                        Log::warning('Could not close an old Kashier session.', [
                            'ticket_payment_id' => $payment->id,
                            'exception' => $exception,
                        ]);
                    }
                }

                $payment->update(['status' => TicketPaymentStatus::Abandoned]);
            });
    }

    /**
     * A full discount leaves nothing to charge, and Kashier won't take a zero payment.
     */
    private function issueFreeTicket(Ticket $ticket): void
    {
        if ($this->issuer->issue($ticket, 'free', TicketStatus::PaymentPending)) {
            $this->issuer->send($ticket);
        }
    }
}
