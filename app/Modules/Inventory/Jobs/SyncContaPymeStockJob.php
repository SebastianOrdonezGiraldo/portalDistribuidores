<?php

namespace App\Modules\Inventory\Jobs;

use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
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

    public function handle(ContaPymeSyncState $syncState): void
    {
        if (! (bool) config('contapyme.enabled')) {
            $syncState->block('La sincronizacion ContaPyme esta deshabilitada en este entorno.');

            return;
        }

        $syncState->markRunning();

        $exitCode = Artisan::call('contapyme:sync-stock');
        $report = $this->report(Artisan::output());

        if ($exitCode !== 0) {
            $syncState->fail($report['summary'], $report);

            throw new RuntimeException($report['summary']);
        }

        $syncState->complete($report['summary'], $report);
        Cache::forget('admin.dashboard.metrics');
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

        $syncState->fail($message, [
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
        ]);
    }

    /**
     * @return array{summary:string, error_count:int, error_groups:array, error_details:array}
     */
    private function report(string $output): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));
        $diagnostics = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'CONTAPYME_DIAGNOSTICS:')) {
                $decoded = json_decode(trim(substr($line, strlen('CONTAPYME_DIAGNOSTICS:'))), true);

                if (is_array($decoded)) {
                    $diagnostics = $decoded;
                }
            }
        }

        $fallbackLines = array_values(array_filter(
            $lines,
            fn (string $line): bool => ! str_starts_with($line, 'CONTAPYME_DIAGNOSTICS:'),
        ));
        $summary = $fallbackLines[array_key_last($fallbackLines)]
            ?? 'ContaPyme no devolvio un resumen de sincronizacion.';

        foreach (array_reverse($lines) as $line) {
            if (str_starts_with($line, 'ContaPyme stock sync:')) {
                $summary = $line;
                break;
            }
        }

        return [
            'summary' => $summary,
            'error_count' => max(0, (int) ($diagnostics['error_count'] ?? 0)),
            'error_groups' => is_array($diagnostics['error_groups'] ?? null)
                ? array_values($diagnostics['error_groups'])
                : [],
            'error_details' => is_array($diagnostics['error_details'] ?? null)
                ? array_values($diagnostics['error_details'])
                : [],
        ];
    }
}
