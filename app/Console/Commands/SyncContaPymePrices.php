<?php

namespace App\Console\Commands;

use App\Modules\Inventory\Services\ContaPymePriceSyncRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SyncContaPymePrices extends Command
{
    protected $signature = 'contapyme:sync-prices
        {--sku= : Sincroniza un solo SKU local}
        {--limit=0 : Limita la cantidad de productos activos}
        {--dry-run : Consulta ContaPyme sin guardar cambios}
        {--force : Ejecuta aunque CONTAPYME_PRICE_SYNC_ENABLED=false}';

    protected $description = 'Sincroniza precios Gold/Silver locales desde ContaPyme';

    public function handle(ContaPymePriceSyncRunner $runner): int
    {
        if (! (bool) config('contapyme.prices.enabled') && ! (bool) $this->option('force')) {
            $this->warn('Sincronización de precios deshabilitada. Define CONTAPYME_PRICE_SYNC_ENABLED=true o usa --force.');

            return self::SUCCESS;
        }

        $report = $runner->run(
            origin: 'command',
            sku: trim((string) $this->option('sku')) ?: null,
            limit: max(0, (int) $this->option('limit')),
            dryRun: (bool) $this->option('dry-run'),
            force: (bool) $this->option('force'),
            emit: function (string $message, string $level): void {
                $level === 'error' ? $this->error($message) : $this->line($message);
            },
        );

        $this->info($report->summary());
        $this->line('CONTAPYME_PRICE_DIAGNOSTICS: '.json_encode($report->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));

        if (($report->stats['failed'] ?? 0) > 0 || ($report->stats['anomalous'] ?? 0) > 0) {
            Log::warning('contapyme.price_sync_command_attention', [
                'run_id' => $report->runId,
                'stats' => $report->stats,
            ]);
        }

        return $report->exitCode();
    }
}
