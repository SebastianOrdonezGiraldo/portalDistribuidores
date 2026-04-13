<?php

namespace App\Modules\AuthAccess\Mail;

use App\Modules\AuthAccess\Models\Distributor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DistributorRegistrationNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Distributor $distributor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de registro de distribuidor | Portal',
        );
    }

    public function content(): Content
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return new Content(
            view: 'emails.auth.distributor-registration-notification',
            with: [
                'distributor' => $this->distributor,
                'adminDistributorsUrl' => $baseUrl.'/admin/distributors',
            ],
        );
    }
}
