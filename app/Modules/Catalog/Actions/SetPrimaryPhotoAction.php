<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPhoto;

class SetPrimaryPhotoAction
{
    public function execute(Product $product, ProductPhoto $photo): ProductPhoto
    {
        $product->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);

        return $photo->refresh();
    }
}

