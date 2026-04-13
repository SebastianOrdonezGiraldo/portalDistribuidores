<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;

class CreateProductAction
{
    public function execute(array $payload): Product
    {
        return Product::create($payload);
    }
}
