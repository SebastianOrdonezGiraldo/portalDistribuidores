<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Jobs\SyncContaPymeStockJob;
use App\Modules\Inventory\Models\ContaPymeSyncRun;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ContaPymeSyncDispatcher
{
    public function __construct(
        private readonly ContaPymeSyncState $state,
    ) {}

    public function dispatchIfAvailable(string $origin = 'manual'): bool
    {
        if (! (bool) config('contapyme.enabled')) {
            Log::debug('contapyme.sync_dispatch_skipped', [
                'origin' => $origin,
                'reason' => 'disabled',
            ]);

            return false;
        }

        $runId = (string) Str::uuid();
        $lockOwner = $this->state->queueWithContext($origin, $runId);

        if ($lockOwner === null) {
            Log::info('contapyme.sync_dispatch_skipped', [
                'origin' => $origin,
                'reason' => 'active_run',
            ]);

            return false;
        }

        try {
            ContaPymeSyncRun::query()->create([
                'id' => $runId,
                'origin' => $origin,
                'mode' => 'full',
                'status' => 'queued',
                'warehouse' => null,
            ]);

            Bus::dispatch(new SyncContaPymeStockJob(
                origin: $origin,
                runId: $runId,
                lockOwner: $lockOwner,
            ));
        } catch (Throwable $e) {
            $message = 'No fue posible encolar la sincronización ContaPyme.';
            $this->state->fail($message, [
                'error_count' => 1,
                'error_groups' => [['message' => $message, 'count' => 1]],
                'error_details' => [[
                    'sku' => null,
                    'phase' => 'encolado',
                    'message' => $message,
                ]],
            ]);
            $this->state->release($lockOwner);

            Log::error('contapyme.sync_dispatch_failed', [
                'origin' => $origin,
                'run_id' => $runId,
                'exception' => $e::class,
            ]);

            try {
                ContaPymeSyncRun::query()->whereKey($runId)->update([
                    'status' => 'failed',
                    'summary' => $message,
                    'failed' => 1,
                    'error_groups' => [['message' => $message, 'count' => 1]],
                    'error_details' => [[
                        'sku' => null,
                        'phase' => 'encolado',
                        'message' => $message,
                    ]],
                ]);
            } catch (Throwable $ignored) {
                report($ignored);
            }

            report($e);

            return false;
        }

        Log::info('contapyme.sync_dispatched', [
            'origin' => $origin,
            'run_id' => $runId,
        ]);

        return true;
    }
}
