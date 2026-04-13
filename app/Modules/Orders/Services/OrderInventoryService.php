<?php

namespace App\Modules\Orders\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
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

        $this->decreaseVariantStock($items);
        $this->decreaseProductStock($items);
    }

    public function increaseForOrder(Order $order): void
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $order->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $this->increaseVariantStock($items);
        $this->increaseProductStock($items);
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function decreaseVariantStock(Collection $items): void
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

            $variant->stock = round(((float) $variant->stock) - $qty, 2);
            $variant->save();
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function decreaseProductStock(Collection $items): void
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

            $product->stock = round(((float) $product->stock) - $qty, 2);
            $product->save();
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function increaseVariantStock(Collection $items): void
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

            $variant->stock = round(((float) $variant->stock) + $qty, 2);
            $variant->save();
        }
    }

    /**
     * @param  Collection<int, OrderItem>  $items
     */
    private function increaseProductStock(Collection $items): void
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

            $product->stock = round(((float) $product->stock) + $qty, 2);
            $product->save();
        }
    }
}
