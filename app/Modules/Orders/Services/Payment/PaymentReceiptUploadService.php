<?php

namespace App\Modules\Orders\Services\Payment;

use App\Modules\Catalog\Security\SafeUploadValidator;
use App\Modules\Catalog\Support\ProductUploadLimits;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\PaymentUploadToken;
use App\Modules\Shared\Enums\PaymentStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stores payment receipts on the private disk (R2) then updates payment_status.
 *
 * Order of operations is mandatory: persist file first, then DB confirming.
 */
class PaymentReceiptUploadService
{
    public function __construct(
        private readonly SafeUploadValidator $safeUploadValidator,
        private readonly PaymentUploadTokenService $tokenService,
    ) {}

    public static function diskName(): string
    {
        return (string) config('filesystems.order_pdfs_disk', 'private');
    }

    /**
     * @throws DomainException
     */
    public function upload(
        Order $order,
        UploadedFile $file,
        ?PaymentUploadToken $token = null,
    ): Order {
        if (! $order->payment_status->allowsReceiptUpload()) {
            throw new DomainException(
                'Este pedido ya no acepta comprobantes de pago. Estado actual: '.$order->payment_status->label().'.'
            );
        }

        if (filled($order->payment_receipt_path) && $order->payment_status === PaymentStatus::Confirming) {
            throw new DomainException('Ya hay un comprobante en revisión para este pedido. No se puede reemplazar todavía.');
        }

        if ($order->payment_status === PaymentStatus::Validated) {
            throw new DomainException('El pago de este pedido ya fue validado.');
        }

        $this->assertAllowedFile($file);

        $diskName = self::diskName();
        $extension = Str::lower((string) $file->getClientOriginalExtension()) ?: 'bin';
        $safeName = $this->safeUploadValidator->sanitizeOriginalFilename($file, $extension);
        $directory = 'orders/payment-receipts/'.$order->id;
        $storedPath = null;

        $attempts = 0;
        $lastException = null;

        while ($attempts < 2) {
            $attempts++;

            try {
                $storedPath = $file->storeAs($directory, Str::uuid()->toString().'.'.$extension, $diskName);

                if (is_string($storedPath) && $storedPath !== '' && Storage::disk($diskName)->exists($storedPath)) {
                    break;
                }

                $storedPath = null;
                $lastException = new DomainException('No fue posible almacenar el comprobante. Intenta de nuevo.');
            } catch (Throwable $exception) {
                $lastException = $exception;
                usleep(200_000 * $attempts);
            }
        }

        if (! is_string($storedPath) || $storedPath === '') {
            throw $lastException instanceof DomainException
                ? $lastException
                : new DomainException('No fue posible almacenar el comprobante. Intenta de nuevo más tarde.');
        }

        try {
            return DB::transaction(function () use ($order, $storedPath, $safeName, $token): Order {
                /** @var Order $locked */
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if (! $locked->payment_status->allowsReceiptUpload()) {
                    throw new DomainException(
                        'Este pedido ya no acepta comprobantes de pago. Estado actual: '.$locked->payment_status->label().'.'
                    );
                }

                if (filled($locked->payment_receipt_path) && $locked->payment_status === PaymentStatus::Confirming) {
                    throw new DomainException('Ya hay un comprobante en revisión para este pedido.');
                }

                $previousPath = $locked->payment_receipt_path;

                $locked->update([
                    'payment_status' => PaymentStatus::Confirming,
                    'payment_receipt_path' => $storedPath,
                    'payment_receipt_filename' => $safeName,
                    'payment_receipt_uploaded_at' => now(),
                    'payment_reservation_expires_at' => null,
                ]);

                if ($token) {
                    $this->tokenService->markConsumed($token);
                }

                if ($previousPath && $previousPath !== $storedPath) {
                    Storage::disk(self::diskName())->delete($previousPath);
                }

                return $locked->refresh();
            });
        } catch (Throwable $exception) {
            Storage::disk($diskName)->delete($storedPath);

            throw $exception instanceof DomainException
                ? $exception
                : new DomainException('No fue posible registrar el comprobante. Intenta de nuevo.');
        }
    }

    private function assertAllowedFile(UploadedFile $file): void
    {
        $mime = Str::lower((string) $file->getMimeType());
        $maxKb = ProductUploadLimits::photoMaxSizeKb();

        if ($file->getSize() > $maxKb * 1024) {
            throw new DomainException(
                'El comprobante no puede superar '.ProductUploadLimits::photoMaxSizeLabel().'.'
            );
        }

        if (str_starts_with($mime, 'image/')) {
            $this->safeUploadValidator->assertSafeImage($file, 'receipt');

            return;
        }

        if (str_contains($mime, 'pdf')) {
            $this->safeUploadValidator->assertSafePdf($file, 'receipt', 'comprobante');

            return;
        }

        throw new DomainException('El comprobante debe ser una imagen (JPG, PNG, WEBP) o un PDF.');
    }
}
