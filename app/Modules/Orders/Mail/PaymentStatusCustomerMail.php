<?php

namespace App\Modules\Orders\Mail;

use App\Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentStatusCustomerMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $kind,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->kind) {
            'validated' => 'Pago validado '.$this->order->oc_number.' | Import Corporal',
            'rejected' => 'Comprobante rechazado '.$this->order->oc_number.' | Import Corporal',
            'expired' => 'Plazo de pago vencido '.$this->order->oc_number.' | Import Corporal',
            default => 'Actualización de pago '.$this->order->oc_number.' | Import Corporal',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.payment-status',
            text: 'emails.orders.payment-status-text',
            with: [
                'order' => $this->order,
                'kind' => $this->kind,
                'catalogUrl' => route('catalog.index'),
                'orderUrl' => route('orders.show', $this->order),
            ],
        );
    }
}
