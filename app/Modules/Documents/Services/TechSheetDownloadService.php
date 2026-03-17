<?php

namespace App\Modules\Documents\Services;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Documents\Models\DocumentDownload;
use Carbon\CarbonImmutable;

class TechSheetDownloadService
{
    public function canDownload(Distributor $distributor, ProductDocument $document, CarbonImmutable $now): bool
    {
        return $this->remainingDownloads($distributor, $document, $now) > 0;
    }

    public function remainingDownloads(Distributor $distributor, ProductDocument $document, CarbonImmutable $month): int
    {
        $count = DocumentDownload::query()
            ->where('distributor_id', $distributor->id)
            ->where('product_document_id', $document->id)
            ->whereBetween('downloaded_at', [
                $month->startOfMonth(),
                $month->endOfMonth(),
            ])
            ->count();

        return max(0, $this->monthlyLimit() - $count);
    }

    private function monthlyLimit(): int
    {
        return (int) config('documents.tech_sheet_monthly_limit', 3);
    }

    public function registerDownload(Distributor $distributor, ProductDocument $document, CarbonImmutable $now): DocumentDownload
    {
        return DocumentDownload::create([
            'distributor_id' => $distributor->id,
            'product_document_id' => $document->id,
            'downloaded_at' => $now,
        ]);
    }
}

