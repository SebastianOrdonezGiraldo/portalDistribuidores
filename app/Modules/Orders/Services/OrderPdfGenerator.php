<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Support\OrderLineVat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Renders and stores order quotation PDFs on the configured private disk.
 *
 * This class is used from queued jobs and on-demand download actions. It also
 * migrates legacy public files to the configured target path before regenerating
 * the PDF, so older orders remain downloadable after storage hardening.
 */
class OrderPdfGenerator
{
    /**
     * Name of the disk that stores order PDFs.
     */
    public static function diskName(): string
    {
        return (string) config('filesystems.order_pdfs_disk', 'private');
    }

    /**
     * Generate or replace the PDF for an order and return its storage path.
     *
     * @throws RuntimeException when the configured disk cannot persist the PDF
     */
    public function generate(Order $order): string
    {
        $path = 'orders/'.$order->oc_number.'.pdf';
        $this->migrateLegacyPdf($path);

        // Siempre refrescamos relaciones para evitar usar una colección de ítems
        // cacheada en memoria que pueda estar desactualizada.
        $order->load('items', 'distributor', 'user');
        Log::debug('order.pdf.generating', [
            'order_id' => $order->id,
            'oc_number' => $order->oc_number,
            'items_count' => $order->items->count(),
            'items' => $order->items->map(fn ($item) => [
                'order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->product_variant_id,
                'sku' => $item->sku_snapshot,
                'product_name' => $item->product_name_snapshot,
                'variant_value' => $item->variant_value_snapshot,
                'qty' => (float) $item->qty,
            ])->values()->all(),
        ]);

        $pdf = Pdf::loadView('orders.pdf', $this->buildViewData($order))
            ->setPaper('a4', 'portrait');

        $written = $this->disk()->put($path, $pdf->output());

        if ($written === false) {
            throw new RuntimeException("No fue posible guardar el PDF de la orden {$order->id}.");
        }

        Storage::disk('public')->delete($path);

        return $path;
    }

    /**
     * Build the view model consumed by resources/views/orders/pdf.blade.php.
     *
     * @return array<string, mixed>
     */
    private function buildViewData(Order $order): array
    {
        $logoPath = public_path('images/import-corporal-logo.png');
        $logoBase64 = null;

        if (is_file($logoPath)) {
            $logoBase64 = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $lineItems = $order->items->map(function ($item) {
            return array_merge([
                'item' => $item,
            ], OrderLineVat::amountsForItem($item));
        });

        $totalFinal = (float) $order->items->sum(fn ($item) => (float) $item->subtotal);

        return [
            'order' => $order,
            'logoBase64' => $logoBase64,
            'lineItems' => $lineItems,
            'totalFinal' => $totalFinal,
            'vatRate' => OrderLineVat::DEFAULT_RATE,
        ];
    }

    /**
     * Move an old public PDF into the protected disk if it still exists there.
     */
    private function migrateLegacyPdf(string $path): void
    {
        $disk = $this->disk();
        $legacyDisk = Storage::disk('public');

        if ($disk->exists($path) || ! $legacyDisk->exists($path)) {
            return;
        }

        $written = $disk->put($path, (string) $legacyDisk->get($path));

        if ($written !== false) {
            $legacyDisk->delete($path);
        }
    }

    private function disk()
    {
        return Storage::disk(self::diskName());
    }
}
