<?php

namespace App\Modules\Orders\Mail;

use App\Modules\Orders\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Cart $cart) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu carrito te espera | Import Corporal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.abandoned-cart-reminder',
            text: 'emails.orders.abandoned-cart-reminder-text',
            with: [
                'cart' => $this->cart,
                'cartUrl' => rtrim((string) config('app.url'), '/').'/cart',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
