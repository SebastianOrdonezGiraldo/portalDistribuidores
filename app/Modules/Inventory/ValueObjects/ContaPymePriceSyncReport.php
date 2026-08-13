<?php

namespace App\Modules\Inventory\ValueObjects;

use Illuminate\Support\Carbon;

final readonly class ContaPymePriceSyncReport
{
    public function __construct(
        public string $runId,
        public string $origin,
        public string $mode,
        public Carbon $startedAt,
        public Carbon $finishedAt,
        /** @var array<string,int> */
        public array $stats,
        /** @var list<array{sku:string,scope:string,message:string}> */
        public array $errors = [],
    ) {}

    public function exitCode(): int
    {
        return ($this->stats['failed'] ?? 0) > 0 && ($this->stats['updated'] ?? 0) === 0
            && ($this->stats['unchanged'] ?? 0) === 0 ? 1 : 0;
    }

    public function summary(): string
    {
        return sprintf(
            'Sincronización de precios ContaPyme: procesados=%d, actualizados=%d, sin cambios=%d, ausentes=%d, anómalos=%d, fallidos=%d',
            $this->stats['processed'] ?? 0,
            $this->stats['updated'] ?? 0,
            $this->stats['unchanged'] ?? 0,
            $this->stats['missing_contapyme'] ?? 0,
            $this->stats['anomalous'] ?? 0,
            $this->stats['failed'] ?? 0,
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'run_id' => $this->runId,
            'origin' => $this->origin,
            'mode' => $this->mode,
            'started_at' => $this->startedAt->toIso8601String(),
            'finished_at' => $this->finishedAt->toIso8601String(),
            'duration_ms' => max(0, (int) $this->startedAt->diffInMilliseconds($this->finishedAt)),
            'summary' => $this->summary(),
            ...$this->stats,
            'errors' => $this->errors,
        ];
    }
}
