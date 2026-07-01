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
    /**
     * Descargar plantilla de importacion de productos.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @response 200 {"content":"Descarga CSV plantilla"}
     * @response 403 {"message":"No autorizado"}
     */
    public function downloadTemplate(): StreamedResponse
    {
        $this->authorize('create', Product::class);

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            if (! is_resource($output)) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['action', 'sku', 'name', 'brand', 'description', 'category_id', 'price', 'stock', 'is_active', 'is_vat_excluded'], ';');
            fclose($output);
        }, 'plantilla_import_productos.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Importar productos desde CSV.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @bodyParam file file required Archivo CSV.
     * @bodyParam default_action string Accion por defecto: upsert, create, update. Example: upsert
     *
     * @response 302 {"redirect":"admin.products.index"}
     * @response 403 {"message":"No autorizado"}
     * @response 422 {"message":"Archivo o datos invalidos"}
     */
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
