<?php

namespace App\Modules\Orders\Jobs;

use App\Modules\Orders\Mail\PaymentStatusCustomerMail;
use App\Modules\Orders\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendPaymentStatusNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $orderId,
        public readonly string $kind,
    ) {}

    public function handle(): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order || ! filled($order->contact_email)) {
            return;
        }

        try {
            Mail::to($order->contact_email)->send(new PaymentStatusCustomerMail($order, $this->kind));
        } catch (Throwable $exception) {
            Log::warning('payment.notification_send_failed', [
                'order_id' => $this->orderId,
                'kind' => $this->kind,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
