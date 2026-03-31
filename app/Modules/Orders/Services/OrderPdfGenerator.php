<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class OrderPdfGenerator
{
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

        if (! $this->shouldRegenerate($path, $order)) {
            return $path;
        }

        $pdf = Pdf::loadView('orders.pdf', $this->buildViewData($order))
            ->setPaper('a4', 'portrait');

        $this->disk()->put($path, $pdf->output());

        Storage::disk('public')->delete($path);

        return $path;
    }

    private function buildViewData(Order $order): array
    {
        $vatRate = (float) config('billing.vat_rate', 0.19);
        $logoPath = public_path('images/import-corporal-logo.png');
        $logoBase64 = null;

        if (is_file($logoPath)) {
            $logoBase64 = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $lineItems = $order->items->map(function ($item) use ($vatRate) {
            $valorUnit = (float) $item->price_each;
            $valorBase = (float) $item->subtotal;
            $valorIva = round($valorBase * $vatRate, 2);
            $valorTotal = $valorBase + $valorIva;

            return [
                'item' => $item,
                'valorUnit' => $valorUnit,
                'valorBase' => $valorBase,
                'valorIva' => $valorIva,
                'valorTotal' => $valorTotal,
            ];
        });

        $totalFinal = (float) $lineItems->sum('valorTotal');

        return [
            'order' => $order,
            'logoBase64' => $logoBase64,
            'lineItems' => $lineItems,
            'totalFinal' => $totalFinal,
            'vatRate' => $vatRate,
        ];
    }

    private function shouldRegenerate(string $path, Order $order): bool
    {
        $disk = $this->disk();

        if (! $disk->exists($path)) {
            return true;
        }

        $templatePath = resource_path('views/orders/pdf.blade.php');

        if (! is_file($templatePath)) {
            return false;
        }

        $templateLastModified = filemtime($templatePath) ?: 0;
        $pdfLastModified = $disk->lastModified($path);
        $orderLastModified = $this->latestOrderDataTimestamp($order);

        return max($templateLastModified, $orderLastModified) > $pdfLastModified;
    }

    private function latestOrderDataTimestamp(Order $order): int
    {
        $orderTimestamp = $order->updated_at?->getTimestamp() ?? 0;

        /** @var Collection<int, \App\Modules\Orders\Models\OrderItem> $items */
        $items = $order->items;
        $itemsTimestamp = (int) $items
            ->map(fn ($item) => $item->updated_at?->getTimestamp() ?? 0)
            ->max();

        return max($orderTimestamp, $itemsTimestamp);
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
