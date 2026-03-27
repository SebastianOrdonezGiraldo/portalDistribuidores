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
        [$photoWidth, $photoHeight] = $this->extractDimensions($file);

        $path = $file->store('products/photos', 'public');

        $photo = $product->photos()->create([
            'path'       => $path,
            'photo_width' => $photoWidth,
            'photo_height' => $photoHeight,
            'sort_order' => $sortOrder,
            'is_primary' => $isFirst,
        ]);

        if ($isFirst) {
            // Delegate to the canonical action to enforce the "single primary" rule.
            $this->setPrimaryPhotoAction->execute($product, $photo);
        }

        return $photo->refresh();
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function extractDimensions(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();

        if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
            return [null, null];
        }

        $size = @getimagesize($realPath);

        if (is_array($size) && isset($size[0], $size[1]) && $size[0] > 0 && $size[1] > 0) {
            return [(int) $size[0], (int) $size[1]];
        }

        $mime = strtolower((string) $file->getMimeType());

        if ($mime !== 'image/svg+xml') {
            return [null, null];
        }

        $svg = @file_get_contents($realPath);

        if (! is_string($svg) || $svg === '') {
            return [null, null];
        }

        if (preg_match('/viewBox\s*=\s*["\'][^"\']*\s([\d.]+)\s([\d.]+)["\']/i', $svg, $matches) === 1) {
            $width = (int) round((float) $matches[1]);
            $height = (int) round((float) $matches[2]);

            if ($width > 0 && $height > 0) {
                return [$width, $height];
            }
        }

        if (
            preg_match('/\bwidth\s*=\s*["\']([\d.]+)(?:px)?["\']/i', $svg, $widthMatch) === 1
            && preg_match('/\bheight\s*=\s*["\']([\d.]+)(?:px)?["\']/i', $svg, $heightMatch) === 1
        ) {
            $width = (int) round((float) $widthMatch[1]);
            $height = (int) round((float) $heightMatch[1]);

            if ($width > 0 && $height > 0) {
                return [$width, $height];
            }
        }

        return [null, null];
    }
}
