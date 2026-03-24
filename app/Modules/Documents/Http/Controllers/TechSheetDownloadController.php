<?php

namespace App\Modules\Documents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Documents\Services\TechSheetDownloadService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechSheetDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        ProductDocument $productDocument,
        TechSheetDownloadService $downloadService,
    ): StreamedResponse|RedirectResponse {
        $productDocument->loadMissing('product');
        $this->authorize('download', $productDocument);

        $user = $request->user();
        $now = CarbonImmutable::now();

        if ($user?->isDistributor()) {
            $distributor = $user->distributor;

            if (! $distributor) {
                return back()->withErrors('Tu usuario no tiene distribuidor asociado.');
            }

            if (! $downloadService->canDownload($distributor, $productDocument, $now)) {
                $remaining = $downloadService->remainingDownloads($distributor, $productDocument, $now);
                $limit = $downloadService->monthlyLimit();

                return back()->withErrors("Límite mensual alcanzado. Te quedan {$remaining} de {$limit} este mes.");
            }

            $downloadService->registerDownload($distributor, $productDocument, $now);
        }

        return Storage::disk('public')->download($productDocument->path, $productDocument->filename);
    }
}
