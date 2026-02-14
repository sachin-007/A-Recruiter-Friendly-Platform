<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invitation $invitation,
        public readonly string $url,
        public readonly string $subjectLine = 'Test Invitation'
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
            with: [
                'invitation' => $this->invitation,
                'url' => $this->url,
            ]
        );
    }
}
