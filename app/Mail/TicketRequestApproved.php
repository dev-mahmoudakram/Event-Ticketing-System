<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketRequestApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public string $paymentUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your ticket request was approved'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-requests.approved',
        );
    }
}
