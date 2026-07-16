<?php

namespace App\Modules\AuthAccess\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de verificación | Portal Distribuidores',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.email-verification-code',
            text: 'emails.auth.email-verification-code-text',
            with: [
                'recipientName' => $this->recipientName,
                'code' => $this->code,
            ],
        );
    }
}
