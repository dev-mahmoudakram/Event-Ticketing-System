<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketIssuer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class TicketPaymentController extends Controller
{
    // will be updated once we integrate with payment gateway

    public function complete(Ticket $ticket, TicketIssuer $issuer): Response
    {
        // Emailed only after the ticket is saved as issued, so nobody receives a QR code for a
        // ticket that doesn't exist. If sending fails the ticket still stands: the page links
        // to it, and the failure is logged for someone to resend.
        if ($issuer->issue($ticket, 'payment_link', TicketStatus::PaymentPending)) {
            try {
                $issuer->send($ticket);
            } catch (Throwable $exception) {
                Log::error('Failed to send issued ticket email.', [
                    'ticket_id' => $ticket->id,
                    'exception' => $exception,
                ]);
            }
        }

        return response()->view('tickets.payment-complete', ['ticket' => $ticket->fresh()]);
    }
}
