<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;

class OrderInventoryService
{
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

            if ($variant->external_stock !== null) {
                $variant->reserved_stock = round(((float) ($variant->reserved_stock ?? 0)) + $qty, 2);
                $variant->stock = round(max(0, (float) $variant->external_stock - (float) $variant->reserved_stock), 2);
            } else {
                $variant->stock = round($previousStock - $qty, 2);
            }

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

            if ($product->external_stock !== null) {
                $product->reserved_stock = round(((float) ($product->reserved_stock ?? 0)) + $qty, 2);
                $product->stock = round(max(0, (float) $product->external_stock - (float) $product->reserved_stock), 2);
            } else {
                $product->stock = round($previousStock - $qty, 2);
            }

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

            if ($variant->external_stock !== null) {
                $variant->reserved_stock = round(max(0, ((float) ($variant->reserved_stock ?? 0)) - $qty), 2);
                $variant->stock = round(max(0, (float) $variant->external_stock - (float) $variant->reserved_stock), 2);
            } else {
                $variant->stock = round($previousStock + $qty, 2);
            }

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

            if ($product->external_stock !== null) {
                $product->reserved_stock = round(max(0, ((float) ($product->reserved_stock ?? 0)) - $qty), 2);
                $product->stock = round(max(0, (float) $product->external_stock - (float) $product->reserved_stock), 2);
            } else {
                $product->stock = round($previousStock + $qty, 2);
            }

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
