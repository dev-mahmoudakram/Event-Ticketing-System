<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Mail\TicketIssued;
use App\Models\Ticket;
use App\Services\TicketQrCode;
use App\Services\WorkshopBooker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TicketPaymentController extends Controller
{
    // will be updated once we integrate with payment gateway

    public function complete(Ticket $ticket): Response
    {
        if ($ticket->status !== TicketStatus::PaymentPending) {
            return response()->view('tickets.payment-complete', ['ticket' => $ticket]);
        }

        $ticket->update([
            'ticket_id' => Str::random(40),
            'is_paid' => true,
            'payment_method' => 'payment_link',
            'status' => TicketStatus::TicketIssued,
        ]);

        // Issued alongside the QR code, and only for a tier that includes workshops.
        (new WorkshopBooker)->issueKeyFor($ticket->fresh('ticketType'));
        $ticket->refresh();

        $qrImage = (new TicketQrCode)->pngFor($ticket);

        Mail::to($ticket->email)->send(new TicketIssued($ticket, $qrImage));

        return response()->view('tickets.payment-complete', ['ticket' => $ticket]);
    }
}
