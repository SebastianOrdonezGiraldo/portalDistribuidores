<?php

namespace App\Modules\Orders\Services\Payment;

use App\Models\User;
use App\Modules\Orders\Jobs\SendPaymentStatusNotificationJob;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Enums\PaymentStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin and system mutations of payment_status, including expiry + HOLD release.
 */
class OrderPaymentService
{
    public function __construct(
        private readonly OrderStatusTransitionService $statusTransitionService,
        private readonly PaymentUploadTokenService $tokenService,
    ) {}

    public function reservationTtlMinutes(): int
    {
        return max(5, (int) config('commerce.payment.manual_reservation_ttl_minutes', 45));
    }

    /**
     * @throws DomainException
     */
    public function validate(Order $order, ?User $actor = null): Order
    {
        if ($order->payment_status !== PaymentStatus::Confirming) {
            throw new DomainException('Solo se pueden validar pedidos con comprobante en revisión.');
        }

        $validated = DB::transaction(function () use ($order): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status !== PaymentStatus::Confirming) {
                throw new DomainException('Solo se pueden validar pedidos con comprobante en revisión.');
            }

            $locked->update([
                'payment_status' => PaymentStatus::Validated,
            ]);

            return $locked->refresh();
        });

        $this->dispatchNotification($validated, 'validated');

        return $validated;
    }

    /**
     * @throws DomainException
     */
    public function reject(Order $order, ?User $actor = null, ?string $note = null): Order
    {
        if ($order->payment_status !== PaymentStatus::Confirming) {
            throw new DomainException('Solo se pueden rechazar comprobantes en revisión.');
        }

        $rejected = DB::transaction(function () use ($order): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->payment_status !== PaymentStatus::Confirming) {
                throw new DomainException('Solo se pueden rechazar comprobantes en revisión.');
            }

            $locked->update([
                'payment_status' => PaymentStatus::Rejected,
                'payment_receipt_path' => null,
                'payment_receipt_filename' => null,
                'payment_receipt_uploaded_at' => null,
                // New upload window with the same reservation TTL.
                'payment_reservation_expires_at' => now()->addMinutes($this->reservationTtlMinutes()),
            ]);

            return $locked->refresh();
        });

        $this->dispatchNotification($rejected, 'rejected');

        return $rejected;
    }

    /**
     * Expire a single pending/rejected upload window: mark expired + cancel (releases HOLD).
     * Idempotent: skips if payment_status is no longer in an expirable state.
     */
    public function expirePendingUpload(Order $order): bool
    {
        try {
            $didExpire = DB::transaction(function () use ($order): bool {
                /** @var Order|null $locked */
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();

                if (! $locked) {
                    return false;
                }

                $expirable = in_array($locked->payment_status, [
                    PaymentStatus::PendingUpload,
                    PaymentStatus::Rejected,
                ], true);

                if (! $expirable) {
                    return false;
                }

                if ($locked->payment_reservation_expires_at === null
                    || $locked->payment_reservation_expires_at->isFuture()) {
                    return false;
                }

                $locked->update([
                    'payment_status' => PaymentStatus::Expired,
                    'payment_reservation_expires_at' => null,
                ]);

                if ($locked->status === OrderStatus::Submitted) {
                    $this->statusTransitionService->transition(
                        $locked,
                        OrderStatus::Cancelled,
                        null,
                        'Pago no completado: reserva de stock expirada.',
                    );
                }

                return true;
            });
        } catch (Throwable $exception) {
            Log::error('payment.expire_failed', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        if ($didExpire) {
            $this->dispatchNotification($order->refresh(), 'expired');
        }

        return $didExpire;
    }

    /**
     * Process all overdue pending_upload / rejected reservations.
     *
     * @return array{expired: int, failed: int}
     */
    public function expireOverdueReservations(): array
    {
        $expired = 0;
        $failed = 0;

        Order::query()
            ->whereIn('payment_status', [
                PaymentStatus::PendingUpload->value,
                PaymentStatus::Rejected->value,
            ])
            ->whereNotNull('payment_reservation_expires_at')
            ->where('payment_reservation_expires_at', '<', now())
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$expired, &$failed): void {
                foreach ($orders as $order) {
                    try {
                        if ($this->expirePendingUpload($order)) {
                            $expired++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::error('payment.expire_batch_item_failed', [
                            'order_id' => $order->id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return ['expired' => $expired, 'failed' => $failed];
    }

    public function issueUploadTokenBestEffort(Order $order): ?string
    {
        try {
            return $this->tokenService->issue($order);
        } catch (Throwable $exception) {
            Log::warning('payment.token_issue_failed', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function dispatchNotification(Order $order, string $kind): void
    {
        try {
            SendPaymentStatusNotificationJob::dispatch($order->id, $kind);
        } catch (Throwable $exception) {
            Log::warning('payment.notification_dispatch_failed', [
                'order_id' => $order->id,
                'kind' => $kind,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
