<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InvenTreeSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventorySyncController extends Controller
{
    public function index(InvenTreeSyncService $syncService): View
    {
        $connectionStatus = $syncService->testConnection();

        return view('admin.inventory.index', [
            'connectionStatus' => $connectionStatus,
        ]);
    }

    public function sync(Request $request, InvenTreeSyncService $syncService): RedirectResponse
    {
        $type = (string) $request->input('type', 'all');

        if (! in_array($type, ['all', 'products', 'stock'], true)) {
            $type = 'all';
        }

        $results = $syncService->syncAll($type);

        if (isset($results['error'])) {
            return redirect()
                ->route('admin.inventory.index')
                ->with('error', 'Error de sincronización: '.$results['error']);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('success', 'Sincronización completada en '.($results['duration_ms'] ?? 0).' ms.')
            ->with('syncResults', $results);
    }

    public function test(InvenTreeSyncService $syncService): RedirectResponse
    {
        $result = $syncService->testConnection();

        if ($result['success']) {
            return redirect()
                ->route('admin.inventory.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with('error', $result['message']);
    }

    public function exportInvenTreeCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Product::class);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            if (! is_resource($output)) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['IPN', 'name', 'pricing_min', 'total_in_stock', 'active']);

            Product::query()
                ->whereNotNull('sku')
                ->where('sku', '!=', '')
                ->orderBy('id')
                ->chunkById(500, function ($products) use ($output): void {
                    foreach ($products as $product) {
                        fputcsv($output, [
                            $product->sku,
                            $product->name,
                            $this->formatDecimal($product->price),
                            $this->formatDecimal($product->stock),
                            $product->is_active ? 'true' : 'false',
                        ]);
                    }
                });

            fclose($output);
        }, 'inventree-productos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formatDecimal(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
