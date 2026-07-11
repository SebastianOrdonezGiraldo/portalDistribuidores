<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\ContaPymeSyncRun;
use App\Modules\Inventory\ValueObjects\ContaPymeStockSyncReport;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ContaPymeStockSyncRunner
{
    private const MAX_VISIBLE_ERRORS = 10;

    /** @var array<string, array{message:string, count:int}> */
    private array $errorGroups = [];

    /** @var list<array{sku:string|null, phase:string, message:string}> */
    private array $errorDetails = [];

    /** @var list<array{sku:string|null, irecurso:string|null, phase:string, message:string}> */
    private array $unmappedDetails = [];

    private bool $dryRun = false;

    public function __construct(
        private readonly ContaPymeInventoryService $inventory,
    ) {}

    /**
     * @param  callable(string, string):void|null  $emit
     */
    public function run(
        string $origin = 'command',
        ?string $sku = null,
        int $limit = 0,
        bool $dryRun = false,
        ?string $runId = null,
        ?callable $emit = null,
    ): ContaPymeStockSyncReport {
        $this->resetDiagnostics();
        $this->dryRun = $dryRun;

        $runId ??= (string) Str::uuid();
        $mode = $sku !== null && trim($sku) !== '' ? 'sku' : 'full';
        $startedAt = now();
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'missing_contapyme' => 0,
            'no_sku' => 0,
            'confirmed_zero' => 0,
            'unmapped' => 0,
            'failed' => 0,
            'skipped_variants' => 0,
        ];

        $this->startRun($runId, $origin, $mode, $startedAt);

        try {
            $products = $this->products($sku, $limit);
            $stats['processed'] = $products->count();
            $stats['no_sku'] = $mode === 'full' ? $this->activeProductsWithoutSku() : 0;

            $simpleProducts = $products
                ->filter(fn (Product $product): bool => $product->activeVariantsCollection()->isEmpty())
                ->values();
            $variantProducts = $products
                ->filter(fn (Product $product): bool => $product->activeVariantsCollection()->isNotEmpty())
                ->values();

            if ($mode === 'sku') {
                foreach ($simpleProducts as $product) {
                    $this->syncPointProduct($product, $dryRun, $stats, $emit);
                }

                return $this->finish($runId, $origin, $mode, $startedAt, $stats);
            }

            $externalStock = $this->loadBulkStock($stats, $simpleProducts, $variantProducts, $emit);

            if ($externalStock === null) {
                return $this->finish($runId, $origin, $mode, $startedAt, $stats);
            }

            $catalog = $this->loadCatalog($stats, $simpleProducts, $variantProducts, $emit);

            if ($catalog === null && (bool) config('contapyme.catalog_reconciliation', true)) {
                return $this->finish($runId, $origin, $mode, $startedAt, $stats);
            }

            $reservations = $this->reservationsByProductId($simpleProducts);

            foreach ($simpleProducts as $product) {
                $this->syncSimpleProduct(
                    product: $product,
                    externalStock: $externalStock,
                    catalogIds: $catalog,
                    reservedStock: (float) ($reservations->get($product->id) ?? 0.0),
                    dryRun: $dryRun,
                    stats: $stats,
                    emit: $emit,
                );
            }

            $this->syncVariantProducts(
                products: $variantProducts,
                externalStock: $externalStock,
                catalogIds: $catalog,
                dryRun: $dryRun,
                stats: $stats,
                emit: $emit,
            );
        } catch (Throwable $e) {
            $this->recordFailure(
                stats: $stats,
                product: null,
                phase: 'ejecucion',
                message: $e->getMessage(),
            );
            $this->emit($emit, 'CONTAPYME_ERROR: la sincronizacion no pudo completarse.', 'error');
        }

        return $this->finish($runId, $origin, $mode, $startedAt, $stats);
    }

    /**
     * @return Collection<int, Product>
     */
    private function products(?string $sku, int $limit): Collection
    {
        $query = Product::query()
            ->whereNotNull('sku')
            ->where('sku', '<>', '')
            ->with([
                'contapymeMapping',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('contapymeMapping'),
            ])
            ->withCount([
                'variants as active_variants_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('id');

        if (filled($sku)) {
            $query->where('sku', trim((string) $sku));
        } else {
            $query->where('is_active', true);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * @param  array<string, int>  $stats
     * @param  Collection<int, Product>  $simpleProducts
     * @param  Collection<int, Product>  $variantProducts
     * @param  callable(string, string):void|null  $emit
     * @return Collection<string, float>|null
     */
    private function loadBulkStock(array &$stats, Collection $simpleProducts, Collection $variantProducts, ?callable $emit): ?Collection
    {
        $mappedVariants = $variantProducts->flatMap(
            fn (Product $product): Collection => $product->activeVariantsCollection()
                ->filter(fn (ProductVariant $variant): bool => filled($variant->contapymeMapping?->irecurso)),
        );

        if ($simpleProducts->isEmpty() && $mappedVariants->isEmpty()) {
            return collect();
        }

        $items = $this->inventory->listProducts();

        if ($this->inventory->lastError() !== null) {
            $this->emit($emit, 'CONTAPYME_ERROR: la sincronizacion masiva fallo; no se modifico stock local.', 'error');

            foreach ($simpleProducts as $product) {
                $this->recordFailure($stats, $product, 'consulta_masiva', $this->inventory->lastError());
            }

            foreach ($variantProducts as $product) {
                foreach ($product->activeVariantsCollection() as $variant) {
                    $this->recordFailure($stats, $product, 'consulta_masiva', $this->inventory->lastError());
                }
            }

            return null;
        }

        $targetIds = collect($simpleProducts
            ->map(fn (Product $product): ?string => $this->externalIdForProduct($product))
            ->all())
            ->merge($mappedVariants->map(fn (ProductVariant $variant): string => trim((string) $variant->contapymeMapping?->irecurso))->all())
            ->filter(fn (?string $externalId): bool => filled($externalId))
            ->unique()
            ->values();
        $warehouseConfirmed = $items->contains(function ($item): bool {
            if ($item->stock !== null) {
                return true;
            }

            return collect(data_get($item->rawData, 'listabodegas', []))
                ->contains(fn (mixed $row): bool => is_array($row)
                    && (string) ($row['iinventario'] ?? '') === (string) config('contapyme.warehouse'));
        });

        if ((bool) config('contapyme.catalog_reconciliation', true) && ! $warehouseConfirmed) {
            $message = 'ContaPyme no permitió confirmar la bodega configurada; se conserva el stock local.';
            $this->emit($emit, 'CONTAPYME_ERROR: '.$message, 'error');

            foreach ($simpleProducts as $product) {
                $this->recordFailure($stats, $product, 'validacion_bodega', $message);
            }

            foreach ($mappedVariants as $variant) {
                $this->recordFailure($stats, $variant->product, 'validacion_bodega', $message);
            }

            return null;
        }

        $invalidWarehouseRows = $items->filter(
            fn ($item): bool => $item->externalId !== null
                && $targetIds->contains((string) $item->externalId)
                && $item->stock === null,
        );

        if ($invalidWarehouseRows->isNotEmpty()) {
            $message = 'ContaPyme no devolvio un saldo valido para la bodega configurada; se conserva el stock local.';
            $this->emit($emit, 'CONTAPYME_ERROR: '.$message, 'error');

            $invalidIds = $invalidWarehouseRows->pluck('externalId')->map(fn ($id): string => (string) $id);

            foreach ($simpleProducts as $product) {
                if ($invalidIds->contains($this->externalIdForProduct($product))) {
                    $this->recordFailure($stats, $product, 'validacion_bodega', $message);
                }
            }

            foreach ($mappedVariants as $variant) {
                if ($invalidIds->contains((string) $variant->contapymeMapping?->irecurso)) {
                    $this->recordFailure($stats, $variant->product, 'validacion_bodega', $message);
                }
            }

            return null;
        }

        return $items
            ->filter(fn ($item): bool => $item->externalId !== null && $item->stock !== null)
            ->groupBy(fn ($item): string => (string) $item->externalId)
            ->map(fn (Collection $rows): float => (float) $rows->sum('stock'));
    }

    /**
     * @param  array<string, int>  $stats
     * @param  Collection<int, Product>  $simpleProducts
     * @param  Collection<int, Product>  $variantProducts
     * @return Collection<string, true>|null
     */
    private function loadCatalog(array &$stats, Collection $simpleProducts, Collection $variantProducts, ?callable $emit): ?Collection
    {
        if (! (bool) config('contapyme.catalog_reconciliation', true)) {
            return null;
        }

        $mappedVariants = $variantProducts->flatMap(
            fn (Product $product): Collection => $product->activeVariantsCollection()
                ->filter(fn (ProductVariant $variant): bool => filled($variant->contapymeMapping?->irecurso)),
        );

        if ($simpleProducts->isEmpty() && $mappedVariants->isEmpty()) {
            return collect();
        }

        $catalog = $this->inventory->listInventoryCatalog();

        if ($catalog !== null) {
            /** @var Collection<string, true> $catalogIds */
            $catalogIds = collect();

            foreach ($catalog as $catalogItem) {
                $catalogIds->put((string) $catalogItem['irecurso'], true);
            }

            return $catalogIds;
        }

        $this->emit($emit, 'CONTAPYME_ERROR: no fue posible validar el catalogo de ContaPyme.', 'error');
        $message = $this->inventory->lastError();

        foreach ($simpleProducts as $product) {
            $this->recordFailure($stats, $product, 'conciliacion_catalogo', $message);
        }

        foreach ($variantProducts as $product) {
            foreach ($product->activeVariantsCollection() as $variant) {
                $this->recordFailure($stats, $product, 'conciliacion_catalogo', $message);
            }
        }

        return null;
    }

    /**
     * @param  Collection<string, float>  $externalStock
     * @param  Collection<string, true>|null  $catalogIds
     * @param  array<string, int>  $stats
     * @param  callable(string, string):void|null  $emit
     */
    private function syncSimpleProduct(
        Product $product,
        Collection $externalStock,
        ?Collection $catalogIds,
        float $reservedStock,
        bool $dryRun,
        array &$stats,
        ?callable $emit,
    ): void {
        $externalId = $this->externalIdForProduct($product);

        if ($externalId === null) {
            $this->recordUnmapped($stats, $product, null, 'mapeo_irecurso', 'El producto no tiene SKU ni mapeo ContaPyme.');

            return;
        }

        $physicalStock = $this->resolvePhysicalStock(
            externalId: $externalId,
            externalStock: $externalStock,
            catalogIds: $catalogIds,
            product: $product,
            stats: $stats,
            emit: $emit,
        );

        if ($physicalStock === null) {
            return;
        }

        $availableStock = round(max(0, $physicalStock - $reservedStock), 2);

        if ($dryRun) {
            $this->emit($emit, "DRY_OK {$product->sku} physical={$physicalStock} reserved={$reservedStock} available={$availableStock}");
            $this->countStockResult($stats, $product->stock, $availableStock);

            return;
        }

        try {
            $result = $this->inventory->syncProductFromPhysicalStock(
                product: $product,
                physicalStock: $physicalStock,
                reservedStock: $reservedStock,
                syncStatus: 'synced',
            );
        } catch (Throwable $e) {
            $this->recordFailure($stats, $product, 'persistencia_local', $e->getMessage());
            $this->emit($emit, "FAILED {$product->sku}");

            return;
        }

        $this->countStockResult($stats, $product->stock, (float) $result['stock'], $result['status']);
        $this->emit($emit, strtoupper($result['status'])." {$product->sku} stock={$result['stock']}");
    }

    /**
     * @param  Collection<string, float>  $externalStock
     * @param  Collection<string, true>|null  $catalogIds
     * @param  array<string, int>  $stats
     * @param  callable(string, string):void|null  $emit
     */
    private function resolvePhysicalStock(
        string $externalId,
        Collection $externalStock,
        ?Collection $catalogIds,
        Product $product,
        array &$stats,
        ?callable $emit,
    ): ?float {
        if ($catalogIds !== null && ! $catalogIds->has($externalId)) {
            $this->recordFailure(
                stats: $stats,
                product: $product,
                phase: 'conciliacion_catalogo',
                message: 'El irecurso no aparece en el catalogo visible de ContaPyme; se conserva el stock local.',
            );

            return null;
        }

        if ($externalStock->has($externalId)) {
            $this->touchMapping($product, $externalId, 'mapped');

            return (float) $externalStock->get($externalId);
        }

        $exists = $catalogIds?->has($externalId) ?? false;

        if (! $exists) {
            $exists = $this->inventory->productExists($externalId);

            if ($exists === null) {
                $this->recordFailure($stats, $product, 'validacion_sku', $this->inventory->lastError());
                $this->emit($emit, "FAILED {$product->sku}");

                return null;
            }

            if (! $exists) {
                $stats['missing_contapyme']++;
                $stats['unchanged']++;
                $this->touchMapping($product, $externalId, 'missing', 'ContaPyme no encontro el irecurso.');

                if (! $this->dryRun) {
                    $this->inventory->markProductMissingInContaPyme($product);
                }

                $message = $this->dryRun
                    ? "DRY_MISSING {$product->sku} local_stock=".$this->displayStock($product->stock).' stock_preserved'
                    : "MISSING_CONTAPYME {$product->sku} stock=".$this->displayStock($product->stock);
                $this->emit($emit, $message);

                return null;
            }
        }

        $stats['confirmed_zero']++;
        $this->touchMapping($product, $externalId, 'mapped');
        Log::info('contapyme.sync_zero_stock_verified', [
            'sku' => $product->sku,
            'product_id' => $product->id,
            'irecurso' => $externalId,
        ]);

        return 0.0;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<string, float>  $externalStock
     * @param  Collection<string, true>|null  $catalogIds
     * @param  array<string, int>  $stats
     * @param  callable(string, string):void|null  $emit
     */
    private function syncVariantProducts(
        Collection $products,
        Collection $externalStock,
        ?Collection $catalogIds,
        bool $dryRun,
        array &$stats,
        ?callable $emit,
    ): void {
        foreach ($products as $product) {
            $variants = $product->activeVariantsCollection();
            $mappedVariants = $variants->filter(fn (ProductVariant $variant): bool => $variant->contapymeMapping?->irecurso !== null);

            if ($mappedVariants->isEmpty()) {
                $stats['skipped_variants']++;

                if (! $dryRun) {
                    $product->forceFill(['stock_sync_status' => 'skipped_variants'])->save();
                }
                Log::warning('contapyme.sync_skipped_variants', [
                    'sku' => $product->sku,
                    'product_id' => $product->id,
                ]);
                $this->emit($emit, "SKIP_VARIANTS {$product->sku}");

                continue;
            }

            foreach ($variants as $variant) {
                $externalId = trim((string) ($variant->contapymeMapping?->irecurso ?? ''));

                if ($externalId === '') {
                    $this->recordUnmapped($stats, $product, null, 'mapeo_variante', 'La variante no tiene irecurso de ContaPyme.');

                    continue;
                }

                $physicalStock = $this->resolvePhysicalStock(
                    externalId: $externalId,
                    externalStock: $externalStock,
                    catalogIds: $catalogIds,
                    product: $product,
                    stats: $stats,
                    emit: $emit,
                );

                if ($physicalStock === null) {
                    continue;
                }

                $reservedStock = $this->reservedQuantityForVariant($variant);
                $availableStock = round(max(0, $physicalStock - $reservedStock), 2);

                if ($dryRun) {
                    $this->emit($emit, "DRY_OK {$product->sku} variant={$variant->id} physical={$physicalStock} reserved={$reservedStock} available={$availableStock}");
                    $this->countStockResult($stats, $variant->stock, $availableStock);

                    continue;
                }

                try {
                    $result = $this->inventory->syncVariantFromPhysicalStock(
                        variant: $variant,
                        physicalStock: $physicalStock,
                        reservedStock: $reservedStock,
                    );
                    $this->countStockResult($stats, $variant->stock, (float) $result['stock'], $result['status']);
                    $this->emit($emit, strtoupper($result['status'])." {$product->sku} variant={$variant->id} stock={$result['stock']}");
                } catch (Throwable $e) {
                    $this->recordFailure($stats, $product, 'persistencia_variante', $e->getMessage());
                    $this->emit($emit, "FAILED {$product->sku} variant={$variant->id}");
                }
            }
        }
    }

    private function externalIdForProduct(Product $product): ?string
    {
        $mapped = trim((string) ($product->contapymeMapping?->irecurso ?? ''));
        $sku = trim((string) $product->sku);

        return $mapped !== '' ? $mapped : ($sku !== '' ? $sku : null);
    }

    private function displayStock(mixed $stock): string
    {
        if (! is_numeric($stock)) {
            return '0';
        }

        return rtrim(rtrim(number_format((float) $stock, 2, '.', ''), '0'), '.');
    }

    /** @param array<string, int> $stats */
    private function syncPointProduct(Product $product, bool $dryRun, array &$stats, ?callable $emit): void
    {
        $externalId = $this->externalIdForProduct($product);

        if ($externalId === null) {
            $this->recordUnmapped($stats, $product, null, 'mapeo_irecurso', 'El producto no tiene SKU ni mapeo ContaPyme.');

            return;
        }

        $exists = $this->inventory->productExists($externalId);

        if ($exists === null) {
            $this->recordFailure($stats, $product, 'validacion_sku', $this->inventory->lastError());
            $this->emit($emit, "FAILED {$product->sku}");

            return;
        }

        if (! $exists) {
            $stats['missing_contapyme']++;
            $stats['unchanged']++;

            if (! $dryRun) {
                $this->inventory->markProductMissingInContaPyme($product);
            }
            $message = $dryRun
                ? "DRY_MISSING {$product->sku} local_stock=".$this->displayStock($product->stock).' stock_preserved'
                : "MISSING_CONTAPYME {$product->sku} stock=".$this->displayStock($product->stock);
            $this->emit($emit, $message);

            return;
        }

        $info = $this->inventory->getProductInfo($externalId);

        if ($info === null || $info->stock === null) {
            $this->recordFailure($stats, $product, 'consulta_puntual', $this->inventory->lastError());
            $this->emit($emit, "FAILED {$product->sku}");

            return;
        }

        $reservedStock = (float) $this->reservationsByProductId(collect([$product]))->get($product->id, 0.0);
        $availableStock = round(max(0, $info->stock - $reservedStock), 2);

        if ($dryRun) {
            $this->countStockResult($stats, $product->stock, $availableStock);
            $this->emit($emit, "DRY_OK {$product->sku} physical={$info->stock} reserved={$reservedStock} available={$availableStock}");

            return;
        }

        try {
            $result = $this->inventory->syncProductFromPhysicalStock($product, $info->stock, $reservedStock);
            $this->countStockResult($stats, $product->stock, (float) $result['stock'], $result['status']);
            $this->emit($emit, strtoupper($result['status'])." {$product->sku} stock={$result['stock']}");
        } catch (Throwable $e) {
            $this->recordFailure($stats, $product, 'persistencia_local', $e->getMessage());
            $this->emit($emit, "FAILED {$product->sku}");
        }
    }

    private function touchMapping(Product $product, string $externalId, string $status, ?string $error = null): void
    {
        $mapping = $product->contapymeMapping;

        if ($this->dryRun || ! $mapping || $mapping->irecurso !== $externalId) {
            return;
        }

        $mapping->forceFill([
            'status' => $status,
            'last_validated_at' => now(),
            'last_seen_at' => $status === 'mapped' ? now() : $mapping->last_seen_at,
            'last_error' => $error,
        ])->save();
    }

    /** @param array<string, int> $stats */
    private function countStockResult(array &$stats, mixed $previous, float $newStock, ?string $status = null): void
    {
        if ($status === 'updated' || ($status === null && (! is_numeric($previous) || abs((float) $previous - $newStock) > 0.00001))) {
            $stats['updated']++;

            return;
        }

        $stats['unchanged']++;
    }

    /** @param array<string, int> $stats */
    private function recordFailure(array &$stats, ?Product $product, string $phase, ?string $message): void
    {
        $error = $this->inventory->diagnosticError($message);
        $stats['failed']++;
        $key = strtolower((string) preg_replace('/\s+/', ' ', trim($error)));
        $this->errorGroups[$key] ??= ['message' => $error, 'count' => 0];
        $this->errorGroups[$key]['count']++;

        if (count($this->errorDetails) < self::MAX_VISIBLE_ERRORS) {
            $this->errorDetails[] = [
                'sku' => $product?->sku !== null ? (string) $product->sku : null,
                'phase' => $phase,
                'message' => $error,
            ];
        }

        Log::error('contapyme.sync_failure', [
            'sku' => $product?->sku,
            'product_id' => $product?->id,
            'product' => $product?->name,
            'phase' => $phase,
            'error' => $error,
        ]);
    }

    /** @param array<string, int> $stats */
    private function recordUnmapped(array &$stats, ?Product $product, ?string $externalId, string $phase, string $message): void
    {
        $stats['unmapped']++;

        if (count($this->unmappedDetails) < self::MAX_VISIBLE_ERRORS) {
            $this->unmappedDetails[] = [
                'sku' => $product?->sku !== null ? (string) $product->sku : null,
                'irecurso' => $externalId,
                'phase' => $phase,
                'message' => $message,
            ];
        }

        if (! $this->dryRun && $product && $product->exists) {
            $product->forceFill(['stock_sync_status' => 'unmapped_contapyme'])->save();
        }

        Log::warning('contapyme.sync_unmapped', [
            'sku' => $product?->sku,
            'product_id' => $product?->id,
            'product' => $product?->name,
            'irecurso' => $externalId,
            'phase' => $phase,
            'message' => $message,
        ]);
    }

    /** @return Collection<int, float> */
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
            ->whereIn('orders.status', array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::inventoryConsuming()))
            ->groupBy('order_items.product_id')
            ->pluck('reserved_stock', 'order_items.product_id')
            ->map(fn ($reserved): float => (float) $reserved);
    }

    private function reservedQuantityForVariant(ProductVariant $variant): float
    {
        return (float) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_variant_id', $variant->id)
            ->whereIn('orders.status', array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::inventoryConsuming()))
            ->sum('order_items.qty');
    }

    private function activeProductsWithoutSku(): int
    {
        return Product::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('sku')->orWhere('sku', ''))
            ->count();
    }

    /** @param callable(string, string):void|null $emit */
    private function emit(?callable $emit, string $message, string $level = 'line'): void
    {
        if ($emit !== null) {
            $emit($message, $level);
        }
    }

    private function resetDiagnostics(): void
    {
        $this->errorGroups = [];
        $this->errorDetails = [];
        $this->unmappedDetails = [];
    }

    private function startRun(string $runId, string $origin, string $mode, $startedAt): void
    {
        ContaPymeSyncRun::query()->updateOrCreate(
            ['id' => $runId],
            [
                'origin' => $origin,
                'mode' => $mode,
                'status' => 'running',
                'warehouse' => (string) config('contapyme.warehouse'),
                'started_at' => $startedAt,
                'finished_at' => null,
                'summary' => null,
            ],
        );
    }

    /** @param array<string, int> $stats */
    private function finish(string $runId, string $origin, string $mode, $startedAt, array $stats): ContaPymeStockSyncReport
    {
        $finishedAt = now();
        $errorGroups = collect($this->errorGroups)->sortByDesc('count')->values()->all();
        $report = new ContaPymeStockSyncReport(
            runId: $runId,
            origin: $origin,
            mode: $mode,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            stats: $stats,
            errorGroups: $errorGroups,
            errorDetails: $this->errorDetails,
            unmappedDetails: $this->unmappedDetails,
        );

        ContaPymeSyncRun::query()->whereKey($runId)->update([
            'status' => $report->exitCode() === 0 ? 'completed' : 'failed',
            'finished_at' => $finishedAt,
            'duration_ms' => $report->durationMs(),
            ...$stats,
            'summary' => $report->summary(),
            'error_groups' => $errorGroups,
            'error_details' => $this->errorDetails,
            'diagnostics' => $report->toArray(),
            'updated_at' => now(),
        ]);

        return $report;
    }
}
