<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use Illuminate\Http\UploadedFile;

class AttachTechSheetAction
{
    public function execute(Product $product, UploadedFile $file): ProductDocument
    {
        $path = $file->store('products/documents', 'public');

        return $product->documents()->create([
            'type' => 'tech_sheet',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
        ]);
    }
}

