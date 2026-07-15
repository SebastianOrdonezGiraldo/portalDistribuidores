<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\CatalogBannerPlacement;
use App\Modules\Catalog\Models\CatalogBanner;
use App\Modules\Catalog\Security\SafeUploadValidator;
use Illuminate\Http\UploadedFile;

class UploadCatalogBannerAction
{
    public function __construct(private readonly SafeUploadValidator $safeUploadValidator) {}

    public function execute(UploadedFile $file, string $title, CatalogBannerPlacement $placement, int $sortOrder): CatalogBanner
    {
        $this->safeUploadValidator->assertSafeImage($file, 'image');

        $dimensions = @getimagesize((string) $file->getRealPath());
        $path = $file->store('catalog/banners', 'public');

        return CatalogBanner::query()->create([
            'title' => $title,
            'placement' => $placement,
            'path' => $path,
            'image_width' => is_array($dimensions) ? (int) ($dimensions[0] ?? 0) ?: null : null,
            'image_height' => is_array($dimensions) ? (int) ($dimensions[1] ?? 0) ?: null : null,
            'sort_order' => $sortOrder,
        ]);
    }
}
