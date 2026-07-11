<?php

namespace App\Modules\Inventory\ValueObjects;

use Illuminate\Support\Carbon;

final class ContaPymeStockSyncReport
{
    /**
     * @param  array<string, int>  $stats
     * @param  list<array{message:string, count:int}>  $errorGroups
     * @param  list<array{sku:string|null, phase:string, message:string}>  $errorDetails
     * @param  list<array{sku:string|null, irecurso:string|null, phase:string, message:string}>  $unmappedDetails
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $origin,
        public readonly string $mode,
        public readonly Carbon $startedAt,
        public readonly Carbon $finishedAt,
        public readonly array $stats,
        public readonly array $errorGroups = [],
        public readonly array $errorDetails = [],
        public readonly array $unmappedDetails = [],
    ) {}

    public function durationMs(): int
    {
        return max(0, (int) $this->startedAt->diffInMilliseconds($this->finishedAt));
    }

    public function exitCode(): int
    {
        return ($this->stats['failed'] ?? 0) > 0
            && ($this->stats['updated'] ?? 0) === 0
            && ($this->stats['unchanged'] ?? 0) === 0
            ? 1
            : 0;
    }

    public function summary(): string
    {
        return sprintf(
            'Sincronización de stock ContaPyme: procesados=%d, actualizados=%d, sin cambios=%d, ausentes en ContaPyme=%d, sin SKU=%d, variantes omitidas=%d, fallidos=%d',
            $this->stats['processed'] ?? 0,
            $this->stats['updated'] ?? 0,
            $this->stats['unchanged'] ?? 0,
            $this->stats['missing_contapyme'] ?? 0,
            $this->stats['no_sku'] ?? 0,
            $this->stats['skipped_variants'] ?? 0,
            $this->stats['failed'] ?? 0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'origin' => $this->origin,
            'mode' => $this->mode,
            'started_at' => $this->startedAt->toIso8601String(),
            'finished_at' => $this->finishedAt->toIso8601String(),
            'duration_ms' => $this->durationMs(),
            'summary' => $this->summary(),
            ...$this->stats,
            'error_count' => count($this->errorDetails) < ($this->stats['failed'] ?? 0)
                ? ($this->stats['failed'] ?? 0)
                : count($this->errorDetails),
            'error_groups' => $this->errorGroups,
            'error_details' => $this->errorDetails,
            'unmapped_details' => $this->unmappedDetails,
        ];
    }
}
