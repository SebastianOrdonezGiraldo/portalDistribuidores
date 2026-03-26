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
