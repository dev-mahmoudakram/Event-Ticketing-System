<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\InvitationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InvitationRequest $invitationRequest) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('We received your request'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-requests.received',
        );
    }
}
