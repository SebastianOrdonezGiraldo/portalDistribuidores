<?php

namespace App\Modules\Inventory\Console\Commands;

use App\Modules\Inventory\Services\InvenTreeSyncService;
use Illuminate\Console\Command;

class SyncInvenTree extends Command
{
    protected $signature = 'inventree:sync
        {--type=all : all|products|stock}
        {--test-connection : Solo probar conexión con InvenTree}
        {--no-detail : Suprimir salida detallada de resultados}';

    protected $description = 'Sincroniza productos y stock desde InvenTree API';

    public function handle(InvenTreeSyncService $syncService): int
    {
        if ($this->option('test-connection')) {
            return $this->testConnection($syncService);
        }

        $type = (string) $this->option('type');

        if (! in_array($type, ['all', 'products', 'stock'], true)) {
            $this->error("Tipo inválido: {$type}. Usa all|products|stock.");

            return self::FAILURE;
        }

        $this->info('Iniciando sincronización desde InvenTree...');
        $this->newLine();

        $results = $syncService->syncAll(function (int $current, int $total, string $phase, string $itemName): void {
            $this->output->write(sprintf("\r<info>[%s]</info> Procesando %d/%d: %s", $phase, $current, $total, $itemName));
        });

        $this->newLine(2);

        if (isset($results['error'])) {
            $this->error('Error: '.$results['error']);

            return self::FAILURE;
        }

        if (! $this->option('no-detail') && ! $this->option('quiet')) {
            $this->table(
                ['Fase', 'Total', 'Creados', 'Actualizados', 'Saltados', 'Errores'],
                [
                    [
                        'Productos',
                        $results['products']['total'] ?? 0,
                        $results['products']['created'] ?? 0,
                        $results['products']['updated'] ?? 0,
                        $results['products']['skipped'] ?? 0,
                        $results['products']['errors'] ?? 0,
                    ],
                    [
                        'Stock',
                        $results['stock']['total'] ?? 0,
                        '-',
                        $results['stock']['updated'] ?? 0,
                        $results['stock']['unmatched'] ?? 0,
                        $results['stock']['errors'] ?? 0,
                    ],
                ]
            );
        }

        $this->info(sprintf(
            'Sincronización completada en %d ms.',
            $results['duration_ms'] ?? 0
        ));

        return self::SUCCESS;
    }

    private function testConnection(InvenTreeSyncService $syncService): int
    {
        $this->info('Probando conexión con InvenTree...');
        $this->newLine();

        $result = $syncService->testConnection();

        if ($result['success']) {
            $this->info('✓ '.$result['message']);
            $this->line('Servidor: '.$result['server']);

            return self::SUCCESS;
        }

        $this->error('✗ '.$result['message']);

        return self::FAILURE;
    }
}
