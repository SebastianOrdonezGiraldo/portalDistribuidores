<?php

namespace App\Modules\Orders\Mail;

use App\Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PaymentReceiptAdminMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $receiptContents,
        public readonly string $receiptFilename,
        public readonly string $receiptMime,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comprobante Oro '.$this->order->oc_number.' | Portal',
        );
    }

    public function content(): Content
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return new Content(
            view: 'emails.orders.payment-receipt-admin',
            text: 'emails.orders.payment-receipt-admin-text',
            with: [
                'order' => $this->order,
                'adminOrderUrl' => $baseUrl.'/admin/orders/'.$this->order->id,
                'paymentMethodLabel' => $this->order->payment_method?->label() ?? '—',
            ],
        );
    }

    public function attachments(): array
    {
        $filename = filled($this->receiptFilename)
            ? $this->receiptFilename
            : ('comprobante-'.$this->order->oc_number.'.'.(Str::afterLast($this->receiptMime, '/') ?: 'bin'));

        return [
            Attachment::fromData(
                fn () => $this->receiptContents,
                $filename,
            )->withMime($this->receiptMime),
        ];
    }
}
