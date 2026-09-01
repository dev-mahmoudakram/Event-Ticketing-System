<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketIssued extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public string $qrImage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your ticket is ready'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tickets.issued');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->qrImage, 'ticket-qr.png')->withMime('image/png'),
        ];
    }
}
