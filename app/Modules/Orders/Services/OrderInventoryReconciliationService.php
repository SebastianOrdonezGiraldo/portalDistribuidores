<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryHold;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reads every held SKU from ContaPyme and updates base stock atomically.
 */
class OrderInventoryReconciliationService
{
    public function __construct(
        private readonly ContaPymeInventoryService $inventory,
    ) {}

    /**
     * @throws DomainException
     */
    public function reconcile(Order $order): void
    {
        /** @var Collection<int, InventoryHold> $holds */
        $holds = $order->activeInventoryHolds()
            ->with('product.contapymeMapping', 'variant.contapymeMapping')
            ->orderBy('id')
            ->get();

        if ($holds->isEmpty()) {
            throw new DomainException('El pedido no tiene un HOLD activo para reconciliar.');
        }

        /** @var array<string, array{product_id:int, variant_id:int|null, stock:float}> $snapshots */
        $snapshots = [];

        foreach ($holds as $hold) {
            $product = $hold->product;
            $variant = $hold->variant;

            if (! $product) {
                throw new DomainException('No fue posible reconciliar inventario: producto del HOLD no encontrado.');
            }

            $externalId = $this->externalId($product, $variant);
            $info = $this->inventory->getProductInfo($externalId);

            if ($info === null || $info->stock === null) {
                $detail = $this->inventory->diagnosticError($this->inventory->lastError());

                throw new DomainException("ContaPyme no devolvió inventario válido para {$externalId}. {$detail}");
            }

            $snapshots[$hold->inventory_key] = [
                'product_id' => (int) $hold->product_id,
                'variant_id' => $hold->product_variant_id !== null ? (int) $hold->product_variant_id : null,
                'stock' => round(max(0, (float) $info->stock), 2),
            ];
        }

        DB::transaction(function () use ($order, $snapshots): void {
            $productIds = collect($snapshots)->pluck('product_id')->unique()->sort()->values()->all();
            $variantIds = collect($snapshots)->pluck('variant_id')->filter()->unique()->sort()->values()->all();

            /** @var Collection<int, Product> $products */
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            /** @var Collection<int, ProductVariant> $variants */
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $variantProductIds = [];

            foreach ($snapshots as $snapshot) {
                /** @var Product|null $product */
                $product = $products->get($snapshot['product_id']);

                if (! $product) {
                    throw new DomainException('No fue posible persistir la reconciliación: producto no encontrado.');
                }

                if ($snapshot['variant_id'] !== null) {
                    /** @var ProductVariant|null $variant */
                    $variant = $variants->get($snapshot['variant_id']);

                    if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                        throw new DomainException('No fue posible persistir la reconciliación: variante no encontrada.');
                    }

                    $previous = is_numeric($variant->stock) ? (float) $variant->stock : null;
                    $variant->forceFill([
                        'stock' => $snapshot['stock'],
                        'stock_synced_at' => now(),
                        'stock_sync_status' => 'synced',
                    ])->save();
                    $variantProductIds[(int) $product->id] = true;

                    if ($previous === null || abs($previous - $snapshot['stock']) > 0.00001) {
                        StockMovement::record(
                            product: $product,
                            variant: $variant,
                            previousStock: $previous,
                            newStock: $snapshot['stock'],
                            source: 'contapyme_sale_reconciliation',
                            order: $order,
                        );
                    }

                    continue;
                }

                $previous = is_numeric($product->stock) ? (float) $product->stock : null;
                $product->forceFill([
                    'stock' => $snapshot['stock'],
                    'stock_synced_at' => now(),
                    'stock_sync_status' => 'synced',
                ])->save();

                if ($previous === null || abs($previous - $snapshot['stock']) > 0.00001) {
                    StockMovement::record(
                        product: $product,
                        previousStock: $previous,
                        newStock: $snapshot['stock'],
                        source: 'contapyme_sale_reconciliation',
                        order: $order,
                    );
                }
            }

            foreach (array_keys($variantProductIds) as $productId) {
                /** @var Product $product */
                $product = $products->get($productId);
                $variantStocks = ProductVariant::query()
                    ->where('product_id', $productId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->pluck('stock');
                $numeric = $variantStocks->filter(fn ($stock): bool => is_numeric($stock));

                $product->forceFill([
                    'stock' => $numeric->isEmpty() ? null : round((float) $numeric->sum(), 2),
                    'stock_synced_at' => now(),
                    'stock_sync_status' => 'synced',
                ])->save();
            }
        });
    }

    private function externalId(Product $product, ?ProductVariant $variant): string
    {
        if ($variant) {
            $mapping = $variant->contapymeMapping;

            if (! $mapping || $mapping->status !== 'mapped' || ! filled($mapping->irecurso)) {
                throw new DomainException(
                    "La variante {$variant->id} no tiene un irecurso de ContaPyme explícito y validado. El HOLD permanece activo."
                );
            }

            return trim((string) $mapping->irecurso);
        }

        $mapping = $product->contapymeMapping;

        if ($mapping && $mapping->status === 'mapped' && filled($mapping->irecurso)) {
            return trim((string) $mapping->irecurso);
        }

        $sku = trim((string) $product->sku);

        if ($sku === '') {
            throw new DomainException('El producto del HOLD no tiene SKU para consultar en ContaPyme.');
        }

        return $sku;
    }
}
