<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Award;
use App\Models\AwardNominee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AwardVoteCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public Award $award, public AwardNominee $nominee) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your voting code: :code', ['code' => $this->code]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.awards.code',
            with: ['event' => $this->award->event],
        );
    }
}
