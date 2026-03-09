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

        $pdf = Pdf::loadView('orders.pdf', [
            'order' => $order->loadMissing('items', 'distributor', 'user'),
        ])->setPaper('a4', 'portrait');

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
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
