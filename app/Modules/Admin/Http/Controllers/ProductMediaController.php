<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Catalog\Models\ProductPhoto;
use App\Modules\Catalog\Models\ProductVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductMediaController extends Controller
{
    /**
     * Eliminar foto de producto.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     * @urlParam photo integer required ID de foto. Example: 20
     *
     * @response 302 {"redirect":"back"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Foto no encontrada para el producto"}
     */
    public function destroyPhoto(Product $product, ProductPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($photo->product_id !== $product->id) {
            abort(404);
        }

        $path = $photo->path;
        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        if ($wasPrimary) {
            $nextPrimary = $product->photos()->orderBy('sort_order')->orderBy('id')->first();

            if ($nextPrimary) {
                $nextPrimary->update(['is_primary' => true]);
            }
        }

        return back()->with('status', 'Foto eliminada.');
    }

    /**
     * Eliminar documento de producto.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     * @urlParam document integer required ID de documento. Example: 25
     *
     * @response 302 {"redirect":"back"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Documento no encontrado para el producto"}
     */
    public function destroyDocument(Product $product, ProductDocument $document): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($document->product_id !== $product->id) {
            abort(404);
        }

        $path = $document->path;
        $document->delete();

        foreach (collect([$document->storageDisk(), 'public'])->unique() as $diskName) {
            if ($path && Storage::disk($diskName)->exists($path)) {
                Storage::disk($diskName)->delete($path);
            }
        }

        return back()->with('status', 'Documento eliminado.');
    }

    /**
     * Descargar documento de producto desde admin.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     * @urlParam document integer required ID de documento. Example: 25
     *
     * @response 200 {"content":"Descarga binaria del documento"}
     * @response 302 {"redirect":"back","message":"Documento no recuperable"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Documento no encontrado para el producto"}
     */
    public function downloadDocument(Product $product, ProductDocument $document): StreamedResponse|RedirectResponse
    {
        $this->authorize('update', $product);

        if ($document->product_id !== $product->id) {
            abort(404);
        }

        $diskName = $document->storageDisk();
        $disk = Storage::disk($diskName);

        if (! $disk->exists($document->path) && $diskName !== 'public' && Storage::disk('public')->exists($document->path)) {
            $written = $disk->put($document->path, (string) Storage::disk('public')->get($document->path));

            if ($written !== false) {
                Storage::disk('public')->delete($document->path);
            }
        }

        if (! $disk->exists($document->path)) {
            if (Storage::disk('public')->exists($document->path)) {
                return Storage::disk('public')->download($document->path, $document->filename);
            }

            return back()->with('error', 'No fue posible recuperar el documento solicitado.');
        }

        return $disk->download($document->path, $document->filename);
    }

    /**
     * Eliminar video de producto.
     *
     * @group Admin
     *
     * @authenticated
     *
     * @urlParam product integer required ID de producto. Example: 10
     * @urlParam video integer required ID de video. Example: 5
     *
     * @response 302 {"redirect":"back"}
     * @response 403 {"message":"No autorizado"}
     * @response 404 {"message":"Video no encontrado para el producto"}
     */
    public function destroyVideo(Product $product, ProductVideo $video): RedirectResponse
    {
        $this->authorize('update', $product);

        if ($video->product_id !== $product->id) {
            abort(404);
        }

        $video->delete();

        return back()->with('status', 'Video eliminado.');
    }
}
