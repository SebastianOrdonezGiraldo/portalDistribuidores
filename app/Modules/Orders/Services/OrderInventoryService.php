<?php

namespace App\Modules\Orders\Services;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryHold;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Owns local order HOLDs without mutating the ContaPyme base stock columns.
 *
 * HOLD rows are the audit source; reserved_stock is the transactional projection
 * used for fast availability reads and row-level oversell protection.
 */
class OrderInventoryService
{
    public function holdForOrder(Order $order, ?User $actor = null, string $reason = 'order_submitted'): void
    {
        $this->synchronizeOrderHolds($order, $actor, $reason);
    }

    public function adjustForEditedOrder(Order $order, ?User $actor = null): void
    {
        $this->synchronizeOrderHolds($order, $actor, 'order_edited');
    }

    public function releaseForOrder(Order $order, ?User $actor = null, string $reason = 'order_cancelled'): void
    {
        $this->applyDesiredHolds($order, [], $actor, $reason);
    }

    public function hasActiveHold(Order $order): bool
    {
        return $order->activeInventoryHolds()->exists();
    }

    /**
     * Backward-compatible aliases for older callers. These now manage HOLDs;
     * they never decrease or restore the stock base.
     */
    public function decreaseForOrder(Order $order): void
    {
        $this->holdForOrder($order);
    }

    public function increaseForOrder(Order $order): void
    {
        $this->releaseForOrder($order);
    }

    private function synchronizeOrderHolds(Order $order, ?User $actor, string $reason): void
    {
        /** @var array<string, array{product_id:int, variant_id:int|null, quantity:float}> $desired */
        $desired = $order->items()
            ->whereNotNull('product_id')
            ->get()
            ->groupBy(fn (OrderItem $item): string => $this->inventoryKey(
                (int) $item->product_id,
                $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
            ))
            ->map(function (Collection $items): array {
                /** @var OrderItem $first */
                $first = $items->first();

                return [
                    'product_id' => (int) $first->product_id,
                    'variant_id' => $first->product_variant_id !== null ? (int) $first->product_variant_id : null,
                    'quantity' => (float) $items->sum(fn (OrderItem $item): float => max(0.0, (float) $item->qty)),
                ];
            })
            ->filter(fn (array $row): bool => $row['quantity'] > 0)
            ->all();

        $this->applyDesiredHolds($order, $desired, $actor, $reason);
    }

    /**
     * @param  array<string, array{product_id:int, variant_id:int|null, quantity:float}>  $desiredRows
     */
    private function applyDesiredHolds(Order $order, array $desiredRows, ?User $actor, string $reason): void
    {
        DB::transaction(function () use ($order, $desiredRows, $actor, $reason): void {
            $desired = collect($desiredRows);
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            /** @var Collection<string, InventoryHold> $existing */
            $existing = InventoryHold::query()
                ->where('order_id', $lockedOrder->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('inventory_key');

            $productIds = $desired->pluck('product_id')
                ->merge($existing->pluck('product_id'))
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $variantIds = $desired->pluck('variant_id')
                ->merge($existing->pluck('product_variant_id'))
                ->filter(fn ($id): bool => $id !== null)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();

            /** @var Collection<int, Product> $products */
            $products = Product::query()
                ->whereIn('id', $productIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            /** @var Collection<int, ProductVariant> $variants */
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $keys = $desired->keys()->merge($existing->keys())->unique()->sort()->values();

            foreach ($keys as $key) {
                $row = $desired->get($key);
                $hold = $existing->get($key);
                $productId = (int) ($row['product_id'] ?? $hold?->product_id ?? 0);
                $variantId = $row['variant_id'] ?? $hold?->product_variant_id;
                $newQuantity = round((float) ($row['quantity'] ?? 0), 2);
                $previousQuantity = $hold?->isActive() ? (float) $hold->quantity : 0.0;
                $delta = round($newQuantity - $previousQuantity, 2);

                /** @var Product|null $product */
                $product = $products->get($productId);

                if (! $product) {
                    throw new DomainException('No fue posible reservar inventario: producto no encontrado.');
                }

                /** @var ProductVariant|null $variant */
                $variant = $variantId !== null ? $variants->get((int) $variantId) : null;

                if ($variantId !== null && (! $variant || (int) $variant->product_id !== $productId)) {
                    throw new DomainException('No fue posible reservar inventario: variante no encontrada.');
                }

                if ($delta > 0) {
                    $this->assertAvailable($product, $variant, $delta);
                }

                if (abs($delta) > 0.00001) {
                    $product->forceFill([
                        'reserved_stock' => round(max(0, (float) $product->reserved_stock + $delta), 2),
                    ])->save();

                    if ($variant) {
                        $variant->reserved_stock = round(max(0, (float) $variant->reserved_stock + $delta), 2);
                        $variant->save();
                    }
                }

                if (! $hold && $newQuantity <= 0) {
                    continue;
                }

                $action = $this->eventAction($hold, $previousQuantity, $newQuantity);

                if (! $hold) {
                    $hold = InventoryHold::create([
                        'order_id' => $lockedOrder->id,
                        'product_id' => $productId,
                        'product_variant_id' => $variantId,
                        'inventory_key' => $key,
                        'quantity' => $newQuantity,
                        'status' => InventoryHold::STATUS_ACTIVE,
                    ]);
                } elseif ($newQuantity > 0) {
                    $hold->update([
                        'quantity' => $newQuantity,
                        'status' => InventoryHold::STATUS_ACTIVE,
                        'released_at' => null,
                        'release_reason' => null,
                    ]);
                } elseif ($hold->isActive()) {
                    $hold->update([
                        'status' => InventoryHold::STATUS_RELEASED,
                        'released_at' => now(),
                        'release_reason' => $reason,
                    ]);
                } else {
                    continue;
                }

                $hold->events()->create([
                    'order_id' => $lockedOrder->id,
                    'user_id' => $actor?->id,
                    'action' => $action,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $newQuantity,
                    'reason' => $reason,
                ]);
            }
        });
    }

    private function assertAvailable(Product $product, ?ProductVariant $variant, float $additionalQuantity): void
    {
        $target = $variant ?? $product;
        $baseStock = $target->stock;

        if (is_numeric($baseStock)) {
            $available = max(0, (float) $baseStock - (float) ($target->reserved_stock ?? 0));

            if ($available + 0.00001 < $additionalQuantity) {
                $label = $variant ? 'la variante seleccionada' : "el producto {$product->sku}";
                $display = max(0, (int) floor($available));

                throw new DomainException("Stock insuficiente en {$label}. Disponible: {$display}.");
            }
        }

        if ($variant && is_numeric($product->stock)) {
            $parentAvailable = max(0, (float) $product->stock - (float) ($product->reserved_stock ?? 0));

            if ($parentAvailable + 0.00001 < $additionalQuantity) {
                $display = max(0, (int) floor($parentAvailable));

                throw new DomainException("Stock insuficiente en el producto {$product->sku}. Disponible: {$display}.");
            }
        }
    }

    private function eventAction(?InventoryHold $hold, float $previous, float $new): string
    {
        if ($hold === null) {
            return 'created';
        }

        if (! $hold->isActive() && $new > 0) {
            return 'reactivated';
        }

        if ($new <= 0) {
            return 'released';
        }

        return $new > $previous ? 'increased' : 'decreased';
    }

    private function inventoryKey(int $productId, ?int $variantId): string
    {
        return $variantId !== null ? 'variant:'.$variantId : 'product:'.$productId;
    }
}
