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

    public function __construct(public readonly int $orderId) {}

    public function handle(OrderPdfGenerator $pdfGenerator): void
    {
        $order = Order::query()->with(['items', 'distributor', 'user'])->find($this->orderId);
        $disk = Storage::disk(OrderPdfGenerator::diskName());

        if (! $order) {
            return;
        }

        if (! $order->pdf_path || ! $disk->exists($order->pdf_path)) {
            $path = $pdfGenerator->generate($order);

            if ($order->pdf_path !== $path) {
                $order->update(['pdf_path' => $path]);
                $order = $order->fresh(['items', 'distributor', 'user']) ?? $order;
            }
        }

        if (! $order->pdf_path || ! $disk->exists($order->pdf_path)) {
            Log::error('order.email.skipped.pdf_missing', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
                'pdf_path' => $order->pdf_path,
            ]);

            return;
        }

        $pdfContents = (string) $disk->get($order->pdf_path);

        // Send both emails independently so that a failure in one does not prevent the other.
        $internalFailed = null;
        try {
            $this->sendInternalNotification($order, $pdfContents);
        } catch (\Throwable $exception) {
            $internalFailed = $exception;
            Log::error('order.email.internal.failed', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            $this->sendCustomerQuotation($order, $pdfContents);
        } catch (\Throwable $exception) {
            Log::error('order.email.customer.failed', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if ($internalFailed !== null) {
            throw $internalFailed;
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
            'recipient' => $this->maskEmail($recipient),
        ]);
    }

    /**
     * Anonimiza un email para logs: "juan.perez@empresa.com" → "ju***@emp***.com"
     * Cumple con Ley 1581 de Habeas Data: no expone datos personales en logs de infraestructura.
     */
    private function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        $maskedLocal = substr($local, 0, min(2, strlen($local))).'***';

        $domainParts = explode('.', $domain, 2);
        $maskedDomain = substr($domainParts[0], 0, min(3, strlen($domainParts[0]))).'***'
            .(isset($domainParts[1]) ? '.'.$domainParts[1] : '');

        return $maskedLocal.'@'.$maskedDomain;
    }

    private function sendCustomerQuotation(Order $order, string $pdfContents): void
    {
        $customerRecipient = trim((string) ($order->contact_email ?? ''));

        if (! filter_var($customerRecipient, FILTER_VALIDATE_EMAIL)) {
            Log::warning('order.email.customer.skipped.invalid_recipient', [
                'order_id' => $order->id,
                'oc_number' => $order->oc_number,
                'contact_email' => $this->maskEmail((string) $order->contact_email),
            ]);

            return;
        }

        Mail::to($customerRecipient)->send(new OrderCreatedCustomerQuotationMail($order, $pdfContents));

        Log::info('order.email.customer.sent', [
            'order_id' => $order->id,
            'oc_number' => $order->oc_number,
            'recipient' => $this->maskEmail($customerRecipient),
        ]);
    }
}
