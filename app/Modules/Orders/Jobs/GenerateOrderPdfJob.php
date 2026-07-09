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

/**
 * Generates the private quotation PDF for one order.
 *
 * The job is unique per order id for a short window to avoid duplicate PDF work
 * when checkout, admin edits or company edits trigger regeneration close
 * together.
 */
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

    /**
     * Use the order id as the uniqueness key for concurrent queue dispatches.
     */
    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function __construct(public readonly int $orderId) {}

    /**
     * Generate the PDF and persist the path back onto the order.
     */
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

    /**
     * Log permanent PDF generation failures after queue retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('order.pdf.failed', [
            'order_id' => $this->orderId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
