<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPhoto;
use App\Modules\Catalog\Services\ProductPhotoVariantService;
use Illuminate\Http\UploadedFile;

class UploadProductPhotoAction
{
    public function __construct(
        private readonly SetPrimaryPhotoAction $setPrimaryPhotoAction,
        private readonly ProductPhotoVariantService $productPhotoVariantService,
    ) {}

    /**
     * @param  array<string, mixed>  $manifestEntry
     * @param  array<string, UploadedFile>  $generatedFiles
     */
    public function execute(
        Product $product,
        UploadedFile $file,
        int $sortOrder = 0,
        array $manifestEntry = [],
        array $generatedFiles = [],
    ): ProductPhoto {
        $isFirst = $product->photos()->doesntExist();
        $localPath = $file->getRealPath() ?: '';

        $path = $file->store('products/photos', 'public');
        $dimensions = $this->productPhotoVariantService->extractDimensions($localPath);
        $variants = $this->productPhotoVariantService->persistClientGeneratedVariants($path, $manifestEntry, $generatedFiles);

        if ($variants === []) {
            $variants = $this->productPhotoVariantService->maybeGenerateFromUpload($file, $path);
        }

        $photo = $product->photos()->create([
            'path'       => $path,
            'width'      => $dimensions['width'],
            'height'     => $dimensions['height'],
            'variants'   => $variants !== [] ? $variants : null,
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
