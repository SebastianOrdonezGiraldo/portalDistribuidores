<?php

namespace App\Modules\Orders\Jobs;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderPdfGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateOrderPdfJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * Tiempo máximo (segundos) que se mantiene el lock de unicidad.
     * Pasado este tiempo, el job puede despacharse de nuevo aunque no haya terminado.
     */
    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function __construct(public readonly int $orderId) {}

    public function handle(OrderPdfGenerator $generator): void
    {
        $order = Order::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        $path = $generator->generate($order);

        if ($order->pdf_path !== $path) {
            $order->update(['pdf_path' => $path]);
        }

        Log::info('order.pdf.generated', [
            'order_id' => $order->id,
            'oc_number' => $order->oc_number,
            'pdf_path' => $path,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('order.pdf.failed', [
            'order_id' => $this->orderId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
