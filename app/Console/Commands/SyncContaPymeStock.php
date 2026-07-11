<?php

namespace App\Console\Commands;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncContaPymeStock extends Command
{
    private const MAX_VISIBLE_ERRORS = 10;

    /** @var array<string, array{message:string, count:int}> */
    private array $diagnosticGroups = [];

    /** @var list<array{sku:string|null, phase:string, message:string}> */
    private array $diagnosticDetails = [];

    private int $diagnosticErrorCount = 0;

    protected $signature = 'contapyme:sync-stock
        {--sku= : Sincroniza un solo SKU}
        {--limit=0 : Limita la cantidad de productos a procesar}
        {--dry-run : Consulta ContaPyme sin guardar cambios}
        {--force : Ejecuta aunque CONTAPYME_SYNC_ENABLED=false}
        {--test-connection : Solo valida autenticacion y conectividad}';

    protected $description = 'Sincroniza disponibilidad local desde ContaPyme por SKU/irecurso';

    public function handle(ContaPymeInventoryService $inventory): int
    {
        $this->resetDiagnostics();

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
            'missing_contapyme' => 0,
            'no_sku' => $sku === '' ? $this->activeProductsWithoutSku() : 0,
            'failed' => 0,
            'skipped_variants' => 0,
        ];

        /** @var Collection<int, Product> $products */
        $products = $query->get();
        $stats['processed'] = $products->count();

        $simpleProducts = $products->reject(function (Product $product) use ($dryRun, &$stats): bool {
            if ((int) ($product->active_variants_count ?? 0) === 0) {
                return false;
            }

            $stats['skipped_variants']++;

            if (! $dryRun) {
                $product->forceFill(['stock_sync_status' => 'skipped_variants'])->save();
            }

            Log::warning('contapyme.sync_skipped_variants', [
                'sku' => $product->sku,
                'product_id' => $product->id,
            ]);
            $this->line("SKIP_VARIANTS {$product->sku}");

            return true;
        })->values();

        if ($simpleProducts->isNotEmpty() && $sku === '') {
            $externalStockBySku = $this->externalStockBySku($inventory);

            if ($externalStockBySku === null) {
                $this->error('CONTAPYME_ERROR: la sincronizacion masiva fallo; no se modifico stock local.');

                $this->logCritical('ContaPyme sync: fallo la sincronizacion masiva', [
                    'error' => $inventory->lastError(),
                    'active_products' => $simpleProducts->count(),
                ]);

                foreach ($simpleProducts as $product) {
                    $this->recordFailure(
                        stats: $stats,
                        inventory: $inventory,
                        product: $product,
                        phase: 'consulta_masiva',
                        message: $inventory->lastError(),
                    );
                }

                return $this->finish($stats, self::FAILURE);
            }
        } else {
            $externalStockBySku = collect();
        }

        $reservations = $this->reservationsByProductId($simpleProducts);

        foreach ($simpleProducts as $product) {
            $reservedStock = (float) ($reservations->get($product->id) ?? 0.0);
            $hasBulkStock = $sku === '' && $externalStockBySku->has((string) $product->sku);

            if ($sku !== '' || ! $hasBulkStock) {
                $exists = $inventory->productExists((string) $product->sku);

                if ($exists === null) {
                    $this->recordFailure(
                        stats: $stats,
                        inventory: $inventory,
                        product: $product,
                        phase: 'validacion_sku',
                        message: $inventory->lastError(),
                    );

                    $this->line(($dryRun ? 'DRY_FAIL' : 'FAILED')." {$product->sku}");

                    continue;
                }

                if (! $exists) {
                    $stats['missing_contapyme']++;
                    $stats['unchanged']++;
                    $localStock = is_numeric($product->stock) ? (float) $product->stock : 0.0;

                    Log::warning('contapyme.sync_missing_sku', [
                        'sku' => $product->sku,
                        'product_id' => $product->id,
                        'local_stock' => $localStock,
                    ]);

                    if ($dryRun) {
                        $this->line("DRY_MISSING {$product->sku} local_stock={$localStock} stock_preserved");

                        continue;
                    }

                    $inventory->markProductMissingInContaPyme($product);
                    $this->line("MISSING_CONTAPYME {$product->sku} stock={$localStock}");

                    continue;
                }
            }

            if ($sku !== '') {
                $info = $inventory->getProductInfo((string) $product->sku);

                if ($info?->stock === null) {
                    $this->recordFailure(
                        stats: $stats,
                        inventory: $inventory,
                        product: $product,
                        phase: 'consulta_stock',
                        message: $inventory->lastError(),
                    );

                    $this->line(($dryRun ? 'DRY_FAIL' : 'FAILED')." {$product->sku}");

                    continue;
                }

                $physicalStock = $info->stock;
            } elseif ($hasBulkStock) {
                $physicalStock = (float) $externalStockBySku->get((string) $product->sku);
            } else {
                $physicalStock = 0.0;

                Log::info('contapyme.sync_zero_stock_verified', [
                    'sku' => $product->sku,
                    'product_id' => $product->id,
                ]);
            }

            $availableStock = round(max(0, $physicalStock - $reservedStock), 2);

            if ($dryRun) {
                $this->line("DRY_OK {$product->sku} physical={$physicalStock} reserved={$reservedStock} available={$availableStock}");

                if (! is_numeric($product->stock) || abs((float) $product->stock - $availableStock) > 0.00001) {
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }

                continue;
            }

            try {
                $result = $inventory->syncProductFromPhysicalStock(
                    product: $product,
                    physicalStock: $physicalStock,
                    reservedStock: $reservedStock,
                    syncStatus: 'synced',
                );
            } catch (Throwable $e) {
                $this->recordFailure(
                    stats: $stats,
                    inventory: $inventory,
                    product: $product,
                    phase: 'persistencia_local',
                    message: $e->getMessage(),
                );

                $this->line("FAILED {$product->sku}");

                continue;
            }
            $status = $result['status'];

            if ($status === 'updated') {
                $stats['updated']++;
            } elseif ($status === 'unchanged') {
                $stats['unchanged']++;
            } else {
                $this->recordFailure(
                    stats: $stats,
                    inventory: $inventory,
                    product: $product,
                    phase: 'persistencia_local',
                    message: 'No fue posible persistir el stock calculado.',
                );
            }

            $stock = $result['stock'] ?? 'null';
            $this->line(strtoupper($status)." {$product->sku} stock={$stock}");
        }

        return $this->finish($stats, $stats['failed'] > 0 && $stats['updated'] === 0 && $stats['unchanged'] === 0
            ? self::FAILURE
            : self::SUCCESS);
    }

    /**
     * @return Collection<string, float>|null null means the external response was not complete.
     */
    private function externalStockBySku(ContaPymeInventoryService $inventory): ?Collection
    {
        $items = $inventory->listProducts();

        if ($inventory->lastError() !== null) {
            return null;
        }

        return $items
            ->filter(fn ($item): bool => $item->externalId !== null && $item->stock !== null)
            ->groupBy(fn ($item): string => (string) $item->externalId)
            ->map(fn (Collection $items): float => (float) $items->sum('stock'));
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return Collection<int, float>
     */
    private function reservationsByProductId(Collection $products): Collection
    {
        $productIds = $products->pluck('id')->all();

        if ($productIds === []) {
            return collect();
        }

        return OrderItem::query()
            ->selectRaw('order_items.product_id, SUM(order_items.qty) as reserved_stock')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereNull('order_items.product_variant_id')
            ->whereIn('orders.status', array_map(
                fn (OrderStatus $status): string => $status->value,
                OrderStatus::inventoryConsuming(),
            ))
            ->groupBy('order_items.product_id')
            ->pluck('reserved_stock', 'order_items.product_id')
            ->map(fn ($reservedStock): float => (float) $reservedStock);
    }

    private function activeProductsWithoutSku(): int
    {
        return Product::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('sku')->orWhere('sku', '');
            })
            ->count();
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function recordFailure(
        array &$stats,
        ContaPymeInventoryService $inventory,
        ?Product $product,
        string $phase,
        ?string $message,
    ): void {
        $error = $inventory->diagnosticError($message);
        $stats['failed']++;
        $this->diagnosticErrorCount++;

        $groupKey = $this->diagnosticGroupKey($error);

        if (! isset($this->diagnosticGroups[$groupKey])) {
            $this->diagnosticGroups[$groupKey] = [
                'message' => $error,
                'count' => 0,
            ];
        }
        $this->diagnosticGroups[$groupKey]['count']++;

        if (count($this->diagnosticDetails) < self::MAX_VISIBLE_ERRORS) {
            $this->diagnosticDetails[] = [
                'sku' => $product?->sku !== null ? (string) $product->sku : null,
                'phase' => $phase,
                'message' => $error,
            ];
        }

        Log::error('contapyme.sync_failure', [
            'sku' => $product?->sku,
            'product_id' => $product?->id,
            'phase' => $phase,
            'error' => $error,
        ]);
    }

    /**
     * @return array{error_count:int, error_groups:list<array{message:string, count:int}>, error_details:list<array{sku:string|null, phase:string, message:string}>}
     */
    private function diagnostics(): array
    {
        return [
            'error_count' => $this->diagnosticErrorCount,
            'error_groups' => collect($this->diagnosticGroups)
                ->sortByDesc('count')
                ->values()
                ->all(),
            'error_details' => $this->diagnosticDetails,
        ];
    }

    private function diagnosticGroupKey(string $message): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($message));

        return strtolower($normalized ?: $message);
    }

    private function resetDiagnostics(): void
    {
        $this->diagnosticGroups = [];
        $this->diagnosticDetails = [];
        $this->diagnosticErrorCount = 0;
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function finish(array $stats, int $exitCode): int
    {
        $this->info(sprintf(
            'ContaPyme stock sync: processed=%d updated=%d unchanged=%d missing_contapyme=%d no_sku=%d skipped_variants=%d failed=%d',
            $stats['processed'],
            $stats['updated'],
            $stats['unchanged'],
            $stats['missing_contapyme'],
            $stats['no_sku'],
            $stats['skipped_variants'],
            $stats['failed'],
        ));

        $diagnostics = json_encode(
            $this->diagnostics(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        );
        $this->line('CONTAPYME_DIAGNOSTICS: '.($diagnostics ?: '{"error_count":0,"error_groups":[],"error_details":[]}'));

        if ($stats['failed'] > 0 || $stats['missing_contapyme'] > $stats['processed'] * 0.5) {
            $this->logCritical('ContaPyme sync: errores detectados', [
                'failed' => $stats['failed'],
                'missing_contapyme' => $stats['missing_contapyme'],
                'updated' => $stats['updated'],
                'processed' => $stats['processed'],
                'exit_code' => $exitCode,
            ]);
        }

        return $exitCode;
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
