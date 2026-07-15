<?php

namespace App\Console\Commands;

use App\Modules\Inventory\Models\ContaPymeSyncRun;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Console\Command;

class RecoverContaPymeSyncState extends Command
{
    protected $signature = 'contapyme:recover-sync-state
        {--older-than=15 : Antiguedad minima en minutos para considerar una ejecucion queued como huerfana}
        {--force : Ejecutar sin confirmacion interactiva}';

    protected $description = 'Marca ejecuciones ContaPyme encoladas y huerfanas como fallidas y libera su estado operativo';

    public function handle(ContaPymeSyncState $state): int
    {
        $olderThan = filter_var($this->option('older-than'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($olderThan === false) {
            $this->error('--older-than debe ser un numero entero mayor o igual a 1.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Confirma que el worker de este entorno esta detenido antes de limpiar el estado ContaPyme.',
        )) {
            $this->warn('Recuperacion cancelada sin modificar datos.');

            return self::SUCCESS;
        }

        $cutoff = now()->subMinutes($olderThan);
        $runs = ContaPymeSyncRun::query()
            ->where('status', 'queued')
            ->whereNull('started_at')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $summary = 'Ejecucion cancelada durante la recuperacion operativa de la cola.';
        $diagnostic = [[
            'message' => $summary,
            'count' => 1,
        ]];

        foreach ($runs as $run) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'failed' => 1,
                'summary' => $summary,
                'error_groups' => $diagnostic,
                'error_details' => [[
                    'sku' => null,
                    'phase' => 'cola',
                    'message' => $summary,
                ]],
            ]);
        }

        $state->clearForRecovery();

        $this->info("Recuperacion completada: {$runs->count()} ejecuciones huerfanas marcadas como fallidas.");

        return self::SUCCESS;
    }
}
