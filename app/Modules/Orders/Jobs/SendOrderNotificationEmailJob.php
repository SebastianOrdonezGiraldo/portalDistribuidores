<?php

namespace App\Modules\Orders\Jobs;

use App\Modules\Orders\Mail\OrderCreatedCustomerQuotationMail;
use App\Modules\Orders\Mail\OrderCreatedNotificationMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SendOrderNotificationEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 300, 600];
    }

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(): void
    {
        $order = Order::query()->with(['items', 'distributor', 'user'])->find($this->orderId);

        if (! $order) {
            return;
        }

        if (! $order->pdf_path || ! Storage::disk('public')->exists($order->pdf_path)) {
            $generator = app(OrderPdfGenerator::class);
            $path = $generator->generate($order);

            if ($order->pdf_path !== $path) {
                $order->update(['pdf_path' => $path]);
                $order = $order->fresh(['items', 'distributor', 'user']) ?? $order;
            }
        }

        if (! $order->pdf_path || ! Storage::disk('public')->exists($order->pdf_path)) {
            Log::error('order.email.skipped.pdf_missing', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
                'pdf_path' => $order->pdf_path,
            ]);

            return;
        }

        $pdfContents = (string) Storage::disk('public')->get($order->pdf_path);

        try {
            $this->sendInternalNotification($order, $pdfContents);
            $this->sendCustomerQuotation($order, $pdfContents);
        } catch (\Throwable $exception) {
            Log::error('order.email.failed', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException($exception->getMessage(), previous: $exception);
        }

        Log::info('order.email.processed', [
            'order_id' => $order->id,
            'oc_number' => $order->oc_number,
        ]);
    }

    private function sendInternalNotification(Order $order, string $pdfContents): void
    {
        $recipient = trim((string) config('mail.order_notification_to'));

        if ($recipient === '') {
            Log::warning('order.email.internal.skipped.no_recipient', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
            ]);

            return;
        }

        Mail::to($recipient)->send(new OrderCreatedNotificationMail($order, $pdfContents));

        Log::info('order.email.internal.sent', [
            'order_id' => $order->id,
            'oc_number' => $order->oc_number,
            'recipient' => $recipient,
        ]);
    }

    private function sendCustomerQuotation(Order $order, string $pdfContents): void
    {
        $customerRecipient = trim((string) ($order->contact_email ?? ''));

        if (! filter_var($customerRecipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('order.email.customer.skipped.invalid_recipient', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
                'contact_email' => $order->contact_email,
            ]);

            return;
        }

        Mail::to($customerRecipient)->send(new OrderCreatedCustomerQuotationMail($order, $pdfContents));

        Log::info('order.email.customer.sent', [
            'order_id' => $order->id,
            'oc_number' => $order->oc_number,
            'recipient' => $customerRecipient,
        ]);
    }
}
