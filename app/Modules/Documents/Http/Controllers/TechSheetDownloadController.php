<?php

namespace App\Modules\Documents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Documents\Services\TechSheetDownloadService;
use App\Modules\Shared\Enums\DocumentType;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TechSheetDownloadController extends Controller
{
    /**
     * Descargar documento protegido de producto.
     *
     * Valida el tipo de documento segun la ruta, aplica policy y controla limites mensuales cuando corresponde.
     *
     * @group Documentos
     *
     * @authenticated
     *
     * @urlParam productDocument integer required ID del documento. Example: 25
     *
     * @response 200 {"content":"Descarga binaria del documento"}
     * @response 302 {"redirect":"back","message":"Documento no disponible o limite alcanzado"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Documento no encontrado o tipo incorrecto"}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function __invoke(
        Request $request,
        ProductDocument $productDocument,
        TechSheetDownloadService $downloadService,
    ): StreamedResponse|RedirectResponse {
        $productDocument->loadMissing('product');

        foreach ($this->routeDocumentTypes() as $routeName => $documentType) {
            if ($request->routeIs($routeName) && $productDocument->type !== $documentType->value) {
                abort(404);
            }
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
        $path = $this->normalizePath($productDocument->path);

        if (! $this->safeExists($disk, $path)) {
            $this->migrateFromLegacyPublicDisk($productDocument);
        }

        if (! $this->safeExists($disk, $path)) {
            $publicDisk = Storage::disk('public');

            if ($this->safeExists($publicDisk, $path)) {
                return $publicDisk->download($path, $productDocument->filename);
            }

            $documentLabel = Str::lower($productDocument->typeLabel());

            return back()->withErrors("No fue posible recuperar el {$documentLabel} solicitado.");
        }

        return $disk->download($path, $productDocument->filename);
    }

    private function migrateFromLegacyPublicDisk(ProductDocument $productDocument): void
    {
        $targetDiskName = $productDocument->storageDisk();

        if ($targetDiskName === 'public') {
            return;
        }

        $targetDisk = Storage::disk($targetDiskName);
        $legacyDisk = Storage::disk('public');
        $path = $this->normalizePath($productDocument->path);

        if ($this->safeExists($targetDisk, $path) || ! $this->safeExists($legacyDisk, $path)) {
            return;
        }

        try {
            $contents = (string) $legacyDisk->get($path);
            $written = $targetDisk->put($path, $contents);
        } catch (Throwable) {
            return;
        }

        if ($written !== false) {
            try {
                $legacyDisk->delete($path);
            } catch (Throwable) {
                // no-op
            }
        }
    }

    private function normalizePath(?string $path): string
    {
        return rtrim(ltrim(trim((string) $path), '/'), '/');
    }

    /**
     * @return array<string, DocumentType>
     */
    private function routeDocumentTypes(): array
    {
        return [
            'documents.tech-sheet.download' => DocumentType::TechSheet,
            'documents.manual.download' => DocumentType::Manual,
            'documents.invima.download' => DocumentType::Invima,
            'documents.quick-guide.download' => DocumentType::QuickGuide,
        ];
    }

    /**
     * @param  mixed  $disk  Typically an instance from Storage::disk()
     */
    private function safeExists(mixed $disk, string $path): bool
    {
        if ($path === '') {
            return false;
        }

        try {
            return is_object($disk) && method_exists($disk, 'exists')
                ? (bool) $disk->exists($path)
                : false;
        } catch (Throwable) {
            return false;
        }
    }
}
