<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPhoto;
use Illuminate\Http\UploadedFile;

class UploadProductPhotoAction
{
    public function __construct(
        private readonly SetPrimaryPhotoAction $setPrimaryPhotoAction,
    ) {}

    public function execute(Product $product, UploadedFile $file, int $sortOrder = 0): ProductPhoto
    {
        $isFirst = $product->photos()->doesntExist();

        $path = $file->store('products/photos', 'public');

        $photo = $product->photos()->create([
            'path'       => $path,
            'sort_order' => $sortOrder,
            'is_primary' => $isFirst,
        ]);

        if ($isFirst) {
            // Delegate to the canonical action to enforce the "single primary" rule.
            $this->setPrimaryPhotoAction->execute($product, $photo);
        }

        return $photo->refresh();
    }
}
