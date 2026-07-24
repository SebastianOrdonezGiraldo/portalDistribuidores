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
 * Notifies the tier inbox when a client uploads a payment receipt.
 *
 * Gold  → mail.gold_payment_receipt_notification_to
 * Silver → mail.silver_payment_receipt_notification_to
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

        if (! $order || ! filled($order->payment_receipt_path)) {
            return;
        }

        $tier = $this->resolveTier($order);

        if ($tier === null) {
            return;
        }

        $to = $this->recipientForTier($tier);

        if ($to === null) {
            return;
        }

        $disk = Storage::disk(PaymentReceiptUploadService::diskName());

        if (! $disk->exists($order->payment_receipt_path)) {
            Log::warning('payment.receipt_admin_email.skipped_missing_file', [
                'order_id' => $order->id,
                'tier' => $tier->value,
                'path' => $order->payment_receipt_path,
            ]);

            return;
        }

        $contents = (string) $disk->get($order->payment_receipt_path);
        $filename = $order->payment_receipt_filename ?: ('comprobante-'.$order->oc_number);
        $mime = $this->guessMime($filename, $order->payment_receipt_path);

        try {
            Mail::to($to)->send(new PaymentReceiptAdminMail(
                $order,
                $contents,
                $filename,
                $mime,
                $tier,
            ));
        } catch (Throwable $exception) {
            Log::error('payment.receipt_admin_email.failed', [
                'order_id' => $order->id,
                'tier' => $tier->value,
                'mailer' => config('mail.default'),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveTier(Order $order): ?DistributorTier
    {
        if ($order->distributor_tier_snapshot instanceof DistributorTier) {
            return $order->distributor_tier_snapshot;
        }

        return $order->distributor?->tier;
    }

    private function recipientForTier(DistributorTier $tier): ?string
    {
        $to = match ($tier) {
            DistributorTier::Gold => trim((string) config('mail.gold_payment_receipt_notification_to')),
            DistributorTier::Silver => trim((string) config('mail.silver_payment_receipt_notification_to')),
        };

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $to;
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
