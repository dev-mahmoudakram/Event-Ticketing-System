<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SponsorRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SponsorRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SponsorRequest $sponsorRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('We received your sponsorship request'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sponsor-requests.submitted',
        );
    }
}
