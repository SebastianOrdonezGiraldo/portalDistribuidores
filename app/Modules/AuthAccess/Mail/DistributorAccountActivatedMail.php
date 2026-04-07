<?php

namespace App\Modules\AuthAccess\Mail;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DistributorAccountActivatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Distributor $distributor,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cuenta ha sido activada | Portal Distribuidores',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.distributor-account-activated',
            with: [
                'user' => $this->user,
                'distributor' => $this->distributor,
                'loginUrl' => rtrim((string) config('app.url'), '/').'/login',
                'resetPasswordUrl' => rtrim((string) config('app.url'), '/').'/forgot-password',
            ],
        );
    }
}

