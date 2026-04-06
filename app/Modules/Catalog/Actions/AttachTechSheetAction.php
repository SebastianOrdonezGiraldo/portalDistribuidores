<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Shared\Enums\DocumentType;
use Illuminate\Http\UploadedFile;

class AttachTechSheetAction
{
    public function __construct(
        private readonly AttachProtectedProductDocumentAction $attachProtectedProductDocumentAction,
    ) {}

    public function execute(Product $product, UploadedFile $file): ProductDocument
    {
        return $this->attachProtectedProductDocumentAction->execute(
            $product,
            $file,
            DocumentType::TechSheet,
            'tech_sheet',
        );
    }
}
