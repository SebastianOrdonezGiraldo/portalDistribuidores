<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVideo;

class AddVideoAction
{
    public function execute(Product $product, string $url, int $sortOrder = 0): ProductVideo
    {
        return $product->videos()->create([
            'url' => $url,
            'sort_order' => $sortOrder,
        ]);
    }
}

