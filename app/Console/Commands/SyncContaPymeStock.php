<?php

namespace App\Console\Commands;

use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Inventory\Services\ContaPymeStockSyncRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncContaPymeStock extends Command
{
    protected $signature = 'contapyme:sync-stock
        {--sku= : Sincroniza un solo SKU}
        {--limit=0 : Limita la cantidad de productos a procesar}
        {--dry-run : Consulta ContaPyme sin guardar cambios}
        {--force : Ejecuta aunque CONTAPYME_SYNC_ENABLED=false}
        {--test-connection : Solo valida autenticacion y conectividad}';

    protected $description = 'Sincroniza disponibilidad local desde ContaPyme por SKU/irecurso';

    public function handle(
        ContaPymeInventoryService $inventory,
        ContaPymeStockSyncRunner $runner,
    ): int {
        if ((bool) $this->option('test-connection')) {
            if ($inventory->testConnection()) {
                $this->info('CONTAPYME_OK: conexion y autenticacion exitosas.');

                return self::SUCCESS;
            }

            $this->error('CONTAPYME_ERROR: no fue posible autenticar o conectar.');

            if ($this->output->isVerbose() && $inventory->lastError() !== null) {
                $this->line('CONTAPYME_DETAIL: '.$inventory->lastError());
            }

            return self::FAILURE;
        }

        if (! (bool) config('contapyme.enabled') && ! (bool) $this->option('force')) {
            $this->warn('Sincronizacion ContaPyme deshabilitada. Define CONTAPYME_SYNC_ENABLED=true o usa --force.');

            return self::SUCCESS;
        }

        $report = $runner->run(
            origin: 'command',
            sku: trim((string) $this->option('sku')) ?: null,
            limit: max(0, (int) $this->option('limit')),
            dryRun: (bool) $this->option('dry-run'),
            emit: function (string $message, string $level): void {
                match ($level) {
                    'error' => $this->error($message),
                    'warning' => $this->warn($message),
                    default => $this->line($message),
                };
            },
        );

        $this->info($report->summary());
        $this->line('CONTAPYME_DIAGNOSTICS: '.json_encode([
            'error_count' => $report->stats['failed'] ?? 0,
            'error_groups' => $report->errorGroups,
            'error_details' => $report->errorDetails,
            'unmapped_count' => $report->stats['unmapped'] ?? 0,
            'unmapped_details' => $report->unmappedDetails,
            'confirmed_zero' => $report->stats['confirmed_zero'] ?? 0,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));

        if (($report->stats['failed'] ?? 0) > 0 || ($report->stats['missing_contapyme'] ?? 0) > ($report->stats['processed'] ?? 0) * 0.5) {
            $this->logCritical('ContaPyme sync: errores detectados', [
                'run_id' => $report->runId,
                'failed' => $report->stats['failed'] ?? 0,
                'missing_contapyme' => $report->stats['missing_contapyme'] ?? 0,
                'unmapped' => $report->stats['unmapped'] ?? 0,
                'updated' => $report->stats['updated'] ?? 0,
                'processed' => $report->stats['processed'] ?? 0,
                'exit_code' => $report->exitCode(),
            ]);
        }

        return $report->exitCode();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logCritical(string $message, array $context): void
    {
        if (filled(config('logging.channels.slack.url'))) {
            Log::channel('slack')->critical($message, $context);

            return;
        }

        Log::critical($message, $context);
    }
}
