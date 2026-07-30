<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
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
            if ($mode === 'sku') {
                $this->syncSkuFromContaPyme(trim((string) $sku), $dryRun, $stats, $emit);

                return $this->finish($runId, $origin, $mode, $startedAt, $stats);
            }

            $products = $this->products(null, $limit);
            $stats['processed'] = $products->count();
            $stats['no_sku'] = $this->activeProductsWithoutSku();

            $externalStock = $this->loadBulkStock($stats, $products, $emit);

            if ($externalStock === null) {
                return $this->finish($runId, $origin, $mode, $startedAt, $stats);
            }

            Log::info('contapyme.sync_bulk_loaded', [
                'run_id' => $runId,
                'bulk_items' => $externalStock->count(),
                'portal_products' => $products->count(),
            ]);

            $reservations = $this->reservationsByProductId($products);

            foreach ($products as $product) {
                $this->syncSimpleProduct(
                    product: $product,
                    externalStock: $externalStock,
                    reservedStock: (float) ($reservations->get($product->id) ?? 0.0),
                    dryRun: $dryRun,
                    stats: $stats,
                    emit: $emit,
                );
            }
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
     * @param  Collection<int, Product>  $products
     * @param  callable(string, string):void|null  $emit
     * @return Collection<string, float>|null
     */
    private function loadBulkStock(array &$stats, Collection $products, ?callable $emit): ?Collection
    {
        if ($products->isEmpty()) {
            return collect();
        }

        $items = $this->inventory->listProducts();

        if ($this->inventory->lastError() !== null) {
            $this->emit($emit, 'CONTAPYME_ERROR: la sincronizacion masiva fallo; no se modifico stock local.', 'error');

            foreach ($products as $product) {
                $this->recordFailure($stats, $product, 'consulta_masiva', $this->inventory->lastError());
            }

            return null;
        }

        return $items
            ->filter(fn ($item): bool => $item->externalId !== null && $item->stock !== null)
            ->groupBy(fn ($item): string => (string) $item->externalId)
            ->map(fn (Collection $rows): float => (float) $rows->sum('stock'));
    }

    /**
     * @param  Collection<string, float>  $externalStock
     * @param  array<string, int>  $stats
     * @param  callable(string, string):void|null  $emit
     */
    private function syncSimpleProduct(
        Product $product,
        Collection $externalStock,
        float $reservedStock,
        bool $dryRun,
        array &$stats,
        ?callable $emit,
    ): void {
        $externalId = trim((string) $product->sku);

        if ($externalId === '') {
            $this->recordUnmapped($stats, $product, null, 'mapeo_irecurso', 'El producto no tiene SKU.');

            return;
        }

        $physicalStock = $this->resolvePhysicalStock(
            externalId: $externalId,
            externalStock: $externalStock,
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
     * @param  array<string, int>  $stats
     * @param  callable(string, string):void|null  $emit
     */
    private function resolvePhysicalStock(
        string $externalId,
        Collection $externalStock,
        Product $product,
        array &$stats,
        ?callable $emit,
    ): ?float {
        if ($externalStock->has($externalId)) {
            return (float) $externalStock->get($externalId);
        }

        $exists = $this->inventory->productExists($externalId);

        if ($exists === null) {
            $this->recordFailure($stats, $product, 'validacion_sku', $this->inventory->lastError());
            $this->emit($emit, "FAILED {$product->sku}");

            return null;
        }

        if (! $exists) {
            $stats['missing_contapyme']++;
            $stats['unchanged']++;

            if (! $this->dryRun) {
                $this->inventory->markProductMissingInContaPyme($product);
            }

            $message = $this->dryRun
                ? "DRY_MISSING {$product->sku} local_stock=".$this->displayStock($product->stock).' stock_preserved'
                : "MISSING_CONTAPYME {$product->sku} stock=".$this->displayStock($product->stock);
            $this->emit($emit, $message);

            return null;
        }

        $stats['confirmed_zero']++;
        Log::info('contapyme.sync_zero_stock_verified', [
            'sku' => $product->sku,
            'product_id' => $product->id,
            'irecurso' => $externalId,
        ]);

        return 0.0;
    }

    /**
     * Query ContaPyme first by irecurso/SKU. Local DB is only used afterwards
     * to persist stock when the portal product already exists.
     *
     * @param  array<string, int>  $stats
     * @param  callable(string, string):void|null  $emit
     */
    private function syncSkuFromContaPyme(string $sku, bool $dryRun, array &$stats, ?callable $emit): void
    {
        $stats['processed'] = 1;

        if ($sku === '') {
            $this->recordFailure($stats, null, 'consulta_puntual', 'SKU vacio.');
            $this->emit($emit, 'FAILED SKU vacio', 'error');

            return;
        }

        $exists = $this->inventory->productExists($sku);

        if ($exists === null) {
            $this->recordFailure($stats, null, 'validacion_sku', $this->inventory->lastError());
            $this->emit($emit, "FAILED {$sku}", 'error');

            return;
        }

        if (! $exists) {
            $stats['missing_contapyme']++;
            $stats['unchanged']++;
            $this->emit($emit, "CONTAPYME_MISSING irecurso={$sku}");

            $product = Product::query()->where('sku', $sku)->first();

            if ($product !== null && ! $dryRun) {
                $this->inventory->markProductMissingInContaPyme($product);
            }

            return;
        }

        $info = $this->inventory->getProductInfo($sku);

        if ($info === null || $info->stock === null) {
            $this->recordFailure($stats, null, 'consulta_puntual', $this->inventory->lastError());
            $this->emit($emit, "FAILED {$sku}", 'error');

            return;
        }

        $physicalStock = (float) $info->stock;

        if ($physicalStock <= 0.00001) {
            $stats['confirmed_zero']++;
        }

        $this->emit($emit, "CONTAPYME_STOCK irecurso={$sku} physical={$this->displayStock($physicalStock)}");

        $product = Product::query()->where('sku', $sku)->first();

        if ($product === null) {
            $stats['unchanged']++;
            $this->emit($emit, "CONTAPYME_ONLY {$sku} (no hay producto local con ese SKU; stock no se escribio)");

            return;
        }

        $reservedStock = (float) $this->reservationsByProductId(collect([$product]))->get($product->id, 0.0);
        $availableStock = round(max(0, $physicalStock - $reservedStock), 2);

        if ($dryRun) {
            $this->countStockResult($stats, $product->stock, $availableStock);
            $this->emit($emit, "DRY_OK {$sku} physical={$this->displayStock($physicalStock)} reserved={$this->displayStock($reservedStock)} available={$this->displayStock($availableStock)} local_stock={$this->displayStock($product->stock)}");

            return;
        }

        try {
            $result = $this->inventory->syncProductFromPhysicalStock($product, $physicalStock, $reservedStock);
            $this->countStockResult($stats, $product->stock, (float) $result['stock'], $result['status']);
            $this->emit($emit, strtoupper($result['status'])." {$sku} stock={$result['stock']}");
        } catch (Throwable $e) {
            $this->recordFailure($stats, $product, 'persistencia_local', $e->getMessage());
            $this->emit($emit, "FAILED {$sku}", 'error');
        }
    }

    private function displayStock(mixed $stock): string
    {
        if (! is_numeric($stock)) {
            return '0';
        }

        return rtrim(rtrim(number_format((float) $stock, 2, '.', ''), '0'), '.');
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
                'warehouse' => null,
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
