<?php

namespace App\Modules\Documents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Documents\Services\TechSheetDownloadService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechSheetDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        ProductDocument $productDocument,
        TechSheetDownloadService $downloadService,
    ): StreamedResponse|RedirectResponse {
        $productDocument->loadMissing('product');
        if ($request->routeIs('documents.tech-sheet.download') && ! $productDocument->isTechSheet()) {
            abort(404);
        }

        if ($request->routeIs('documents.manual.download') && ! $productDocument->isManual()) {
            abort(404);
        }

        $this->authorize('download', $productDocument);

        $user = $request->user();
        $now = CarbonImmutable::now();

        if ($productDocument->shouldTrackDownloads() && $user?->isDistributor()) {
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

        $disk = Storage::disk($productDocument->storageDisk());

        if (! $disk->exists($productDocument->path)) {
            $this->migrateFromLegacyPublicDisk($productDocument);
        }

        if (! $disk->exists($productDocument->path)) {
            if (Storage::disk('public')->exists($productDocument->path)) {
                return Storage::disk('public')->download($productDocument->path, $productDocument->filename);
            }

            $documentLabel = Str::lower($productDocument->typeLabel());

            return back()->withErrors("No fue posible recuperar el {$documentLabel} solicitado.");
        }

        return $disk->download($productDocument->path, $productDocument->filename);
    }

    private function migrateFromLegacyPublicDisk(ProductDocument $productDocument): void
    {
        $targetDiskName = $productDocument->storageDisk();

        if ($targetDiskName === 'public') {
            return;
        }

        $targetDisk = Storage::disk($targetDiskName);
        $legacyDisk = Storage::disk('public');

        if ($targetDisk->exists($productDocument->path) || ! $legacyDisk->exists($productDocument->path)) {
            return;
        }

        $written = $targetDisk->put($productDocument->path, (string) $legacyDisk->get($productDocument->path));

        if ($written !== false) {
            $legacyDisk->delete($productDocument->path);
        }
    }
}
