<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OrderPdfGenerator
{
    private const VAT_RATE = 0.13;

    public static function diskName(): string
    {
        return (string) config('filesystems.order_pdfs_disk', 'private');
    }

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

    private function buildViewData(Order $order): array
    {
        $vatRate = self::VAT_RATE;
        $vatDivisor = $vatRate > -1 ? (1 + $vatRate) : 1.0;
        $logoPath = public_path('images/import-corporal-logo.png');
        $logoBase64 = null;

        if (is_file($logoPath)) {
            $logoBase64 = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $lineItems = $order->items->map(function ($item) use ($vatDivisor) {
            $valorUnitConIva = (float) $item->price_each;
            $valorUnit = round($valorUnitConIva / $vatDivisor, 2);
            $valorIva = round($valorUnitConIva - $valorUnit, 2);
            $valorTotal = round($valorUnit + $valorIva, 2);
            $valorTotalLinea = round((float) $item->qty * $valorTotal, 2);

            return [
                'item' => $item,
                'valorUnit' => $valorUnit,
                'valorIva' => $valorIva,
                'valorTotal' => $valorTotal,
                'valorTotalLinea' => $valorTotalLinea,
            ];
        });

        $totalFinal = (float) $order->items->sum(fn ($item) => (float) $item->subtotal);

        return [
            'order' => $order,
            'logoBase64' => $logoBase64,
            'lineItems' => $lineItems,
            'totalFinal' => $totalFinal,
            'vatRate' => $vatRate,
        ];
    }

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
