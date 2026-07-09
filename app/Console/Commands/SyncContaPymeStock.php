<?php

namespace App\Console\Commands;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use Illuminate\Console\Command;

class SyncContaPymeStock extends Command
{
    protected $signature = 'contapyme:sync-stock
        {--sku= : Sincroniza un solo SKU}
        {--limit=0 : Limita la cantidad de productos a procesar}
        {--dry-run : Consulta ContaPyme sin guardar cambios}
        {--force : Ejecuta aunque CONTAPYME_SYNC_ENABLED=false}
        {--test-connection : Solo valida autenticacion y conectividad}';

    protected $description = 'Sincroniza stock local desde ContaPyme por SKU/irecurso';

    public function handle(ContaPymeInventoryService $inventory): int
    {
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

        $sku = trim((string) $this->option('sku'));
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $query = Product::query()
            ->whereNotNull('sku')
            ->where('sku', '<>', '')
            ->withCount([
                'variants as active_variants_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('id');

        if ($sku !== '') {
            $query->where('sku', $sku);
        } else {
            $query->where('is_active', true);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0,
            'skipped_variants' => 0,
        ];

        /** @var iterable<int, Product> $products */
        $products = $query->cursor();

        foreach ($products as $product) {
            $stats['processed']++;

            if ((int) ($product->active_variants_count ?? 0) > 0) {
                $stats['skipped_variants']++;

                if (! $dryRun) {
                    $product->forceFill(['stock_sync_status' => 'skipped_variants'])->save();
                }

                $this->line("SKIP_VARIANTS {$product->sku}");

                continue;
            }

            if ($dryRun) {
                $info = $inventory->getProductInfo((string) $product->sku);

                if ($info?->stock === null) {
                    $stats['failed']++;
                    $this->line("DRY_FAIL {$product->sku}");
                } else {
                    $stats['unchanged']++;
                    $this->line("DRY_OK {$product->sku} stock={$info->stock}");
                }

                continue;
            }

            $result = $inventory->syncProduct($product);
            $status = $result['status'];

            if ($status === 'updated') {
                $stats['updated']++;
            } elseif ($status === 'unchanged') {
                $stats['unchanged']++;
            } else {
                $stats['failed']++;
            }

            $stock = $result['stock'] ?? 'null';
            $this->line(strtoupper($status)." {$product->sku} stock={$stock}");
        }

        $this->info(sprintf(
            'ContaPyme stock sync: processed=%d updated=%d unchanged=%d failed=%d skipped_variants=%d',
            $stats['processed'],
            $stats['updated'],
            $stats['unchanged'],
            $stats['failed'],
            $stats['skipped_variants'],
        ));

        return $stats['failed'] > 0 && $stats['updated'] === 0 && $stats['unchanged'] === 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
