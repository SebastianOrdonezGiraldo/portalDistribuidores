<?php

namespace App\Modules\Orders\Mail;

use App\Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCreatedNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $pdfContents,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva CTC '.$this->order->oc_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.created',
            text: 'emails.orders.created-text',
            with: [
                'order' => $this->order,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContents,
                $this->order->oc_number.'.pdf',
            )
                ->withMime('application/pdf'),
        ];
    }
}
