<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class UpdateProductAction
{
    public function execute(Product $product, array $payload): Product
    {
        $product->update($payload);

        return $product->refresh();
    }
}

