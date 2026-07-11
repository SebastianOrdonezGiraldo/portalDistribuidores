<?php

namespace App\Console\Commands;

use App\Modules\Inventory\Services\ContaPymeInventoryService;
use Illuminate\Console\Command;

class DiagnoseContaPyme extends Command
{
    protected $signature = 'contapyme:diagnose {--json : Imprime el diagnóstico como JSON}';

    protected $description = 'Diagnostica autenticación y estado del Agente ContaPyme sin modificar inventario';

    public function handle(ContaPymeInventoryService $inventory): int
    {
        $diagnostics = $inventory->diagnose();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($diagnostics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
        } else {
            $this->line('Autenticación: '.($diagnostics['authenticated'] ? 'OK' : 'FALLÓ'));
            $this->line('Estado del Agente: '.($diagnostics['agent_status'] ?? 'No disponible'));

            foreach ($diagnostics['auth_metadata'] as $key => $value) {
                $this->line(ucfirst((string) $key).': '.$value);
            }

            if ($diagnostics['error'] !== null) {
                $this->error('Error: '.$diagnostics['error']);
            }
        }

        return $diagnostics['authenticated'] && $diagnostics['error'] === null
            ? self::SUCCESS
            : self::FAILURE;
    }
}
