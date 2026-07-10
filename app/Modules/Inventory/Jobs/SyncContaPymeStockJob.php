<?php

namespace App\Modules\Inventory\Jobs;

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
        $summary = $this->summary(Artisan::output());

        if ($exitCode !== 0) {
            throw new RuntimeException($summary);
        }

        $syncState->complete($summary);
        Cache::forget('admin.dashboard.metrics');
    }

    public function failed(?Throwable $exception): void
    {
        app(ContaPymeSyncState::class)->fail($exception?->getMessage() ?? 'La sincronizacion ContaPyme no finalizo correctamente.');
    }

    private function summary(string $output): string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));

        foreach (array_reverse($lines) as $line) {
            if (str_starts_with($line, 'ContaPyme stock sync:')) {
                return $line;
            }
        }

        return $lines[array_key_last($lines)] ?? 'ContaPyme no devolvio un resumen de sincronizacion.';
    }
}
