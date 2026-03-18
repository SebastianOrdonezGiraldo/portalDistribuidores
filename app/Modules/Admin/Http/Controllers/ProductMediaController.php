<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Catalog\Models\ProductPhoto;
use App\Modules\Catalog\Models\ProductVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

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

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('status', 'Documento eliminado.');
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
