<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;

/**
 * Applies order-driven stock mutations for products and variants.
 *
 * The service is called from order creation and status transitions. It locks the
 * affected rows, enforces known numeric stock and records every mutation through
 * StockMovement for auditability.
 */
class OrderInventoryService
{
    /**
     * Decrease stock for every order item that has numeric stock.
     *
     * Non-numeric/null stock is treated as unmanaged and is left unchanged.
     *
     * @throws DomainException when a referenced product/variant is missing or stock is insufficient
     */
    public function decreaseForOrder(Order $order): void
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $order->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $this->decreaseVariantStock($items, $order);
        $this->decreaseProductStock($items, $order);
    }

    /**
     * Restore stock for an order that leaves an inventory-consuming status.
     *
     * Missing or unmanaged rows are skipped during restoration because the
     * original deduction may not have happened.
     */
    public function increaseForOrder(Order $order): void
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $order->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $this->increaseVariantStock($items, $order);
        $this->increaseProductStock($items, $order);
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function decreaseVariantStock(Collection $items, Order $order): void
    {
        $requiredByVariant = $items
            ->filter(fn (OrderItem $item) => $item->product_variant_id !== null)
            ->groupBy(fn (OrderItem $item) => (int) $item->product_variant_id)
            ->map(fn (Collection $group) => (int) $group->sum(fn (OrderItem $item) => max(0, (int) $item->qty)))
            ->filter(fn (int $qty) => $qty > 0);

        if ($requiredByVariant->isEmpty()) {
            return;
        }

        $variants = ProductVariant::query()
            ->whereIn('id', $requiredByVariant->keys()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($requiredByVariant as $variantId => $qty) {
            /** @var ProductVariant|null $variant */
            $variant = $variants->get((int) $variantId);

            if (! $variant) {
                throw new DomainException('No fue posible actualizar inventario: variante no encontrada.');
            }

            if (! is_numeric($variant->stock)) {
                continue;
            }

            $available = max(0, (int) floor((float) $variant->stock));

            if ($available < $qty) {
                throw new DomainException("Stock insuficiente en variante {$variant->id}. Disponible: {$available}.");
            }

            $previousStock = (float) $variant->stock;
            $variant->stock = round($previousStock - $qty, 2);

            $variant->save();

            StockMovement::record(
                product: null,
                variant: $variant,
                previousStock: $previousStock,
                newStock: (float) $variant->stock,
                source: 'order_deduction',
                order: $order,
                orderQty: (float) $qty,
            );
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function decreaseProductStock(Collection $items, Order $order): void
    {
        $requiredByProduct = $items
            ->filter(fn (OrderItem $item) => $item->product_variant_id === null && $item->product_id !== null)
            ->groupBy(fn (OrderItem $item) => (int) $item->product_id)
            ->map(fn (Collection $group) => (int) $group->sum(fn (OrderItem $item) => max(0, (int) $item->qty)))
            ->filter(fn (int $qty) => $qty > 0);

        if ($requiredByProduct->isEmpty()) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $requiredByProduct->keys()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($requiredByProduct as $productId => $qty) {
            /** @var Product|null $product */
            $product = $products->get((int) $productId);

            if (! $product) {
                throw new DomainException('No fue posible actualizar inventario: producto no encontrado.');
            }

            if (! is_numeric($product->stock)) {
                continue;
            }

            $available = max(0, (int) floor((float) $product->stock));

            if ($available < $qty) {
                throw new DomainException("Stock insuficiente en producto {$product->sku}. Disponible: {$available}.");
            }

            $previousStock = (float) $product->stock;
            $product->stock = round($previousStock - $qty, 2);

            $product->save();

            StockMovement::record(
                product: $product,
                previousStock: $previousStock,
                newStock: (float) $product->stock,
                source: 'order_deduction',
                order: $order,
                orderQty: (float) $qty,
            );
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function increaseVariantStock(Collection $items, Order $order): void
    {
        $requiredByVariant = $items
            ->filter(fn (OrderItem $item) => $item->product_variant_id !== null)
            ->groupBy(fn (OrderItem $item) => (int) $item->product_variant_id)
            ->map(fn (Collection $group) => (int) $group->sum(fn (OrderItem $item) => max(0, (int) $item->qty)))
            ->filter(fn (int $qty) => $qty > 0);

        if ($requiredByVariant->isEmpty()) {
            return;
        }

        $variants = ProductVariant::query()
            ->whereIn('id', $requiredByVariant->keys()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($requiredByVariant as $variantId => $qty) {
            /** @var ProductVariant|null $variant */
            $variant = $variants->get((int) $variantId);

            if (! $variant || ! is_numeric($variant->stock)) {
                continue;
            }

            $previousStock = (float) $variant->stock;
            $variant->stock = round($previousStock + $qty, 2);

            $variant->save();

            StockMovement::record(
                product: null,
                variant: $variant,
                previousStock: $previousStock,
                newStock: (float) $variant->stock,
                source: 'order_restoration',
                order: $order,
                orderQty: (float) $qty,
            );
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function increaseProductStock(Collection $items, Order $order): void
    {
        $requiredByProduct = $items
            ->filter(fn (OrderItem $item) => $item->product_variant_id === null && $item->product_id !== null)
            ->groupBy(fn (OrderItem $item) => (int) $item->product_id)
            ->map(fn (Collection $group) => (int) $group->sum(fn (OrderItem $item) => max(0, (int) $item->qty)))
            ->filter(fn (int $qty) => $qty > 0);

        if ($requiredByProduct->isEmpty()) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', $requiredByProduct->keys()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($requiredByProduct as $productId => $qty) {
            /** @var Product|null $product */
            $product = $products->get((int) $productId);

            if (! $product || ! is_numeric($product->stock)) {
                continue;
            }

            $previousStock = (float) $product->stock;
            $product->stock = round($previousStock + $qty, 2);

            $product->save();

            StockMovement::record(
                product: $product,
                previousStock: $previousStock,
                newStock: (float) $product->stock,
                source: 'order_restoration',
                order: $order,
                orderQty: (float) $qty,
            );
        }
    }
}
