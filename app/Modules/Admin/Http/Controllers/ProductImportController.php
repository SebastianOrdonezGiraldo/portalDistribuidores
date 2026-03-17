<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\ImportProductsRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductBulkImportService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    public function downloadTemplate(): StreamedResponse
    {
        $this->authorize('create', Product::class);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            if (! is_resource($output)) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['action', 'sku', 'name', 'brand', 'description', 'category_id', 'price', 'stock', 'is_active'], ';');
            fclose($output);
        }, 'plantilla_import_productos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(
        ImportProductsRequest $request,
        ProductBulkImportService $bulkImportService,
    ): RedirectResponse {
        $this->authorize('create', Product::class);

        $report = $bulkImportService->importFromCsv(
            $request->file('file')->getRealPath(),
            (string) $request->input('default_action', 'upsert'),
        );

        $message = sprintf(
            'Importación finalizada. Filas: %d, creados: %d, actualizados: %d, omitidos: %d, errores: %d.',
            (int) $report['total_rows'],
            (int) $report['created'],
            (int) $report['updated'],
            (int) $report['skipped'],
            count($report['errors']),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', $message)
            ->with('importReport', $report);
    }
}
