<?php

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Models\ContaPymeSyncRun;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Inventory\Services\ContaPymeStockSyncRunner;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class SyncContaPymeStockJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public string $origin = 'manual',
        public ?string $runId = null,
        public ?string $lockOwner = null,
    ) {}

    public function handle(
        ContaPymeSyncState $syncState,
        ContaPymeStockSyncRunner $runner,
    ): void {
        if (! (bool) config('contapyme.enabled')) {
            $syncState->block('La sincronizacion ContaPyme esta deshabilitada en este entorno.');
            $syncState->release($this->lockOwner);

            return;
        }

        try {
            $syncState->markRunning();
            $runId = $this->runId ?? $syncState->status()['run_id'] ?? null;
            $report = $runner->run(
                origin: $this->origin,
                runId: $runId,
            );

            $this->runId = $report->runId;

            if ($report->exitCode() !== 0) {
                $syncState->fail($report->summary(), $report->toArray());

                throw new RuntimeException($report->summary());
            }

            $syncState->complete($report->summary(), $report->toArray());
            Cache::forget('admin.dashboard.metrics');
        } finally {
            $syncState->release($this->lockOwner);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $syncState = app(ContaPymeSyncState::class);

        if (($syncState->status()['state'] ?? null) === 'failed') {
            return;
        }

        $message = app(ContaPymeInventoryService::class)->diagnosticError(
            $exception?->getMessage(),
            'La sincronizacion ContaPyme no finalizo correctamente.',
        );
        $diagnostics = [
            'error_count' => 1,
            'error_groups' => [[
                'message' => $message,
                'count' => 1,
            ]],
            'error_details' => [[
                'sku' => null,
                'phase' => 'job',
                'message' => $message,
            ]],
        ];

        $syncState->fail($message, $diagnostics);
        $syncState->release($this->lockOwner);

        if ($this->runId !== null) {
            ContaPymeSyncRun::query()->whereKey($this->runId)->update([
                'status' => 'failed',
                'finished_at' => now(),
                'summary' => $message,
                'failed' => 1,
                'error_groups' => $diagnostics['error_groups'],
                'error_details' => $diagnostics['error_details'],
            ]);
        }
    }
}
