<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPhoto;
use Illuminate\Http\UploadedFile;

class UploadProductPhotoAction
{
    public function execute(Product $product, UploadedFile $file, int $sortOrder = 0): ProductPhoto
    {
        $path = $file->store('products/photos', 'public');

        $photo = $product->photos()->create([
            'path' => $path,
            'sort_order' => $sortOrder,
            'is_primary' => $product->photos()->doesntExist(),
        ]);

        if ($photo->is_primary) {
            $this->setOnlyPrimary($product, $photo);
        }

        return $photo;
    }

    private function setOnlyPrimary(Product $product, ProductPhoto $photo): void
    {
        $product->photos()->where('id', '!=', $photo->id)->update(['is_primary' => false]);
    }
}
