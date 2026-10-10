<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketPaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketPayment;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Decides whether a payment link was paid, and issues the ticket when it was.
 *
 * The answer always comes from asking Kashier directly — never from a webhook body or the
 * customer's redirect — and the amount, currency and order reference must match what we
 * asked for. The webhook, the customer's return and the scheduled sync all come through
 * here, often at the same moment; only the first to record the payment issues the ticket.
 */
class TicketPaymentConfirmer
{
    public function __construct(
        private readonly KashierGateway $gateway,
        private readonly TicketIssuer $issuer,
    ) {}

    /**
     * Bring one payment up to date with Kashier and return its status.
     *
     * @throws PaymentGatewayException when Kashier can't be asked
     */
    public function confirm(TicketPayment $payment): TicketPaymentStatus
    {
        if ($payment->status !== TicketPaymentStatus::Pending || $payment->session_id === null) {
            return $payment->status;
        }

        $session = $this->gateway->fetchSession($payment->session_id);
        $status = TicketPaymentStatus::fromKashier($session['status']);

        if ($status === null) {
            return TicketPaymentStatus::Pending;
        }

        if ($status === TicketPaymentStatus::Paid && ! $this->matchesWhatWasAsked($payment, $session)) {
            Log::error('Kashier reports a payment that does not match the link we sent; the ticket was not issued.', [
                'ticket_payment_id' => $payment->id,
                'kashier' => $session,
            ]);

            return TicketPaymentStatus::Pending;
        }

        // Conditional, so of several callers racing on the same payment exactly one records it.
        $recorded = TicketPayment::query()
            ->whereKey($payment->id)
            ->where('status', TicketPaymentStatus::Pending)
            ->update([
                'status' => $status,
                'paid_at' => $status === TicketPaymentStatus::Paid ? now() : null,
            ]);

        $payment->refresh();

        if ($recorded === 1 && $status === TicketPaymentStatus::Paid) {
            $this->issueTicketFor($payment);
        }

        return $payment->status;
    }

    /**
     * Check every open link on a ticket, so a payment Kashier has taken is never missed.
     */
    public function confirmOpenPayments(Ticket $ticket): void
    {
        $ticket->payments()
            ->where('status', TicketPaymentStatus::Pending)
            ->get()
            ->each(fn (TicketPayment $payment) => $this->confirm($payment));
    }

    /**
     * Remember the Kashier transaction reference from a signed webhook or redirect.
     */
    public function rememberTransaction(TicketPayment $payment, mixed $transactionId): void
    {
        if (is_string($transactionId) && $transactionId !== '' && $payment->transaction_id === null) {
            $payment->update(['transaction_id' => mb_substr($transactionId, 0, 100)]);
        }
    }

    /**
     * @param  array{status: string, merchantOrderId: string|null, amount: string|null, currency: string|null}  $session
     */
    private function matchesWhatWasAsked(TicketPayment $payment, array $session): bool
    {
        return $session['merchantOrderId'] === $payment->merchant_order_id
            && $session['amount'] !== null
            && is_numeric($session['amount'])
            && $this->gateway->formatAmount($payment->amount) === number_format((float) $session['amount'], 2, '.', '')
            && strtoupper((string) $session['currency']) === strtoupper($payment->currency);
    }

    private function issueTicketFor(TicketPayment $payment): void
    {
        $ticket = $payment->ticket;

        if (! $this->issuer->issue($ticket, 'kashier', TicketStatus::PaymentPending)) {
            // Paid on a link the ticket no longer needed: already paid through another link,
            // or cancelled meanwhile. The money arrived, so someone has to refund it.
            Log::critical('A Kashier payment arrived for a ticket that was not awaiting payment. Refund it.', [
                'ticket_id' => $ticket->id,
                'ticket_status' => $ticket->fresh()?->status->value,
                'ticket_payment_id' => $payment->id,
                'merchant_order_id' => $payment->merchant_order_id,
            ]);

            return;
        }

        // The ticket stands even if the email doesn't go: the return page links to it and the
        // failure is logged for someone to resend.
        try {
            $this->issuer->send($ticket);
        } catch (Throwable $exception) {
            Log::error('Failed to send issued ticket email.', [
                'ticket_id' => $ticket->id,
                'exception' => $exception,
            ]);
        }
    }
}
