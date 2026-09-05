<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\TicketQrCode;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TicketController extends Controller
{
    /**
     * Show an issued ticket on the web, so it can be opened on a phone or printed.
     *
     * The link is guarded by the ticket's own random id rather than a login, because there is
     * no account to log into: whoever holds the ticket holds the link. A ticket that has not
     * been issued has nothing to show.
     */
    public function show(Ticket $ticket, string $ticketId): View
    {
        if ($ticket->ticket_id === null || ! hash_equals($ticket->ticket_id, $ticketId)) {
            throw new NotFoundHttpException;
        }

        if ($ticket->status !== TicketStatus::TicketIssued && $ticket->status !== TicketStatus::CheckedIn) {
            throw new NotFoundHttpException;
        }

        return view('tickets.show', [
            'ticket' => $ticket->load('event', 'ticketType'),
            'qrSrc' => (new TicketQrCode)->dataUriFor($ticket),
        ]);
    }
}
