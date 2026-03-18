<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class OrderPdfGenerator
{
    public function generate(Order $order): string
    {
        $path = 'orders/'.$order->oc_number.'.pdf';

        if (! $this->shouldRegenerate($path)) {
            return $path;
        }

        $order->loadMissing('items', 'distributor', 'user');

        $pdf = Pdf::loadView('orders.pdf', $this->buildViewData($order))
            ->setPaper('a4', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    private function buildViewData(Order $order): array
    {
        $vatRate  = (float) config('billing.vat_rate', 0.19);
        $logoPath = public_path('images/import-corporal-logo.png');
        $logoBase64 = null;

        if (is_file($logoPath)) {
            $logoBase64 = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $lineItems = $order->items->map(function ($item) use ($vatRate) {
            $valorUnit  = (float) $item->price_each;
            $valorBase  = (float) $item->subtotal;
            $valorIva   = round($valorBase * $vatRate, 2);
            $valorTotal = $valorBase + $valorIva;

            return [
                'item'       => $item,
                'valorUnit'  => $valorUnit,
                'valorBase'  => $valorBase,
                'valorIva'   => $valorIva,
                'valorTotal' => $valorTotal,
            ];
        });

        $totalFinal = (float) $lineItems->sum('valorTotal');

        return [
            'order'      => $order,
            'logoBase64' => $logoBase64,
            'lineItems'  => $lineItems,
            'totalFinal' => $totalFinal,
            'vatRate'    => $vatRate,
        ];
    }

    private function shouldRegenerate(string $path): bool
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return true;
        }

        $templatePath = resource_path('views/orders/pdf.blade.php');

        if (! is_file($templatePath)) {
            return false;
        }

        $templateLastModified = filemtime($templatePath) ?: 0;
        $pdfLastModified = $disk->lastModified($path);

        return $templateLastModified > $pdfLastModified;
    }
}
