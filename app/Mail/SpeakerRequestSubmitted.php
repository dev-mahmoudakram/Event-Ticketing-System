<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SpeakerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SpeakerRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SpeakerRequest $speakerRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('We received your speaker request'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.speaker-requests.submitted',
        );
    }
}
