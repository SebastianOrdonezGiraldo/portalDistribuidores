<?php

namespace App\Modules\Documents\Services;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Documents\Models\DocumentDownload;
use Carbon\CarbonImmutable;

/**
 * Enforces monthly download limits for tracked protected product documents.
 *
 * The public controller owns authorization and file delivery; this service only
 * answers quota questions and records successful downloads using the configured
 * business timezone.
 */
class TechSheetDownloadService
{
    /**
     * Determine if a distributor still has quota for this document in the month.
     */
    public function canDownload(Distributor $distributor, ProductDocument $document, CarbonImmutable $now): bool
    {
        return $this->remainingDownloads($distributor, $document, $now) > 0;
    }

    /**
     * Return remaining monthly downloads for this distributor/document pair.
     */
    public function remainingDownloads(Distributor $distributor, ProductDocument $document, CarbonImmutable $moment): int
    {
        [$monthStartUtc, $monthEndUtc] = $this->monthWindowUtc($moment);

        $count = DocumentDownload::query()
            ->where('distributor_id', $distributor->id)
            ->where('product_document_id', $document->id)
            ->whereBetween('downloaded_at', [
                $monthStartUtc,
                $monthEndUtc,
            ])
            ->count();

        return max(0, $this->monthlyLimit() - $count);
    }

    /**
     * Configured monthly limit, clamped to zero to support disabling downloads.
     */
    public function monthlyLimit(): int
    {
        return max(0, (int) config('documents.tech_sheet_monthly_limit', 2));
    }

    /**
     * Record a successful download after policy and quota checks have passed.
     */
    public function registerDownload(Distributor $distributor, ProductDocument $document, CarbonImmutable $now): DocumentDownload
    {
        return DocumentDownload::create([
            'distributor_id' => $distributor->id,
            'product_document_id' => $document->id,
            'downloaded_at' => $now->setTimezone('UTC'),
        ]);
    }

    /**
     * Build the quota window in UTC while respecting the business timezone.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function monthWindowUtc(CarbonImmutable $moment): array
    {
        $localizedMoment = $moment->setTimezone($this->monthlyTimezone());

        return [
            $localizedMoment->startOfMonth()->setTimezone('UTC'),
            $localizedMoment->endOfMonth()->setTimezone('UTC'),
        ];
    }

    private function monthlyTimezone(): string
    {
        return (string) config('documents.tech_sheet_monthly_timezone', 'America/Bogota');
    }
}
