<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ticket;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TicketQrCode
{
    /**
     * What the QR code carries.
     *
     * Deliberately the ticket's own random id and nothing else — not a link. A phone camera
     * pointed at the code sees a meaningless string and can do nothing with it, so the only
     * way to check somebody in is through the signed-in registration portal.
     */
    public function payloadFor(Ticket $ticket): ?string
    {
        return $ticket->ticket_id;
    }

    /**
     * The code as raw PNG bytes, for attaching to an email.
     */
    public function pngFor(Ticket $ticket): ?string
    {
        $payload = $this->payloadFor($ticket);

        if ($payload === null) {
            return null;
        }

        return (new PngWriter)->write(new QrCode(data: $payload))->getString();
    }

    /**
     * The code inlined as a data URI, so a ticket page prints and saves as a single file.
     */
    public function dataUriFor(Ticket $ticket): ?string
    {
        $png = $this->pngFor($ticket);

        return $png === null ? null : 'data:image/png;base64,'.base64_encode($png);
    }
}
