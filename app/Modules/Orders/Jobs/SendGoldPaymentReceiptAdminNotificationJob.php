<?php

namespace App\Modules\Orders\Jobs;

use App\Modules\Orders\Mail\PaymentReceiptAdminMail;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\Payment\PaymentReceiptUploadService;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Notifies the configured admin inbox when a Gold-tier client uploads a receipt.
 *
 * Runs synchronously (same pattern as registration admin notify) so local/dev
 * without queue:work still delivers to Mailtrap/SMTP immediately.
 */
class SendGoldPaymentReceiptAdminNotificationJob
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderId,
    ) {}

    public function handle(): void
    {
        $order = Order::query()->with('distributor')->find($this->orderId);

        if (! $order || ! $this->isGoldOrder($order)) {
            return;
        }

        if (! filled($order->payment_receipt_path)) {
            return;
        }

        $to = trim((string) config('mail.gold_payment_receipt_notification_to'));

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $disk = Storage::disk(PaymentReceiptUploadService::diskName());

        if (! $disk->exists($order->payment_receipt_path)) {
            Log::warning('payment.gold_receipt_admin_email.skipped_missing_file', [
                'order_id' => $order->id,
                'path' => $order->payment_receipt_path,
            ]);

            return;
        }

        $contents = (string) $disk->get($order->payment_receipt_path);
        $filename = $order->payment_receipt_filename ?: ('comprobante-'.$order->oc_number);
        $mime = $this->guessMime($filename, $order->payment_receipt_path);

        try {
            Mail::to($to)->send(new PaymentReceiptAdminMail($order, $contents, $filename, $mime));
        } catch (Throwable $exception) {
            Log::error('payment.gold_receipt_admin_email.failed', [
                'order_id' => $order->id,
                'mailer' => config('mail.default'),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isGoldOrder(Order $order): bool
    {
        if ($order->distributor_tier_snapshot === DistributorTier::Gold) {
            return true;
        }

        return $order->distributor?->tier === DistributorTier::Gold;
    }

    private function guessMime(string $filename, string $path): string
    {
        $extension = Str::lower((string) pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === '') {
            $extension = Str::lower((string) pathinfo($path, PATHINFO_EXTENSION));
        }

        return match ($extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }
}
