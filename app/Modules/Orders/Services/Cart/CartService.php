<?php

namespace App\Modules\Orders\Services\Cart;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'orders.cart.items';
    private ?Collection $resolvedItems = null;

    public function add(Product $product, int $qty = 1, string $unitLabel = 'unidad', ?ProductVariant $variant = null): void
    {
        $items = $this->rawItems();
        $lineKey = $this->buildLineKey($product->id, $variant?->id);

        if (! isset($items[$lineKey])) {
            $items[$lineKey] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'qty' => 0,
                'unit_label' => $unitLabel,
            ];
        }

        $items[$lineKey]['qty'] = max(1, (int) $items[$lineKey]['qty'] + $qty);
        $items[$lineKey]['unit_label'] = $unitLabel;

        Session::put(self::SESSION_KEY, $items);
        $this->resolvedItems = null;
    }

    /**
     * @param array<int|string, mixed> $quantities
     */
    public function update(array $quantities): void
    {
        $items = $this->rawItems();

        foreach ($quantities as $lineKey => $qty) {
            $key = (string) $lineKey;

            if (! array_key_exists($key, $items)) {
                continue;
            }

            $value = (int) $qty;

            if ($value <= 0) {
                unset($items[$key]);
                continue;
            }

            $items[$key]['qty'] = $value;
        }

        Session::put(self::SESSION_KEY, $items);
        $this->resolvedItems = null;
    }

    public function remove(string $lineKey): void
    {
        $items = $this->rawItems();
        unset($items[$lineKey]);
        Session::put(self::SESSION_KEY, $items);
        $this->resolvedItems = null;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        $this->resolvedItems = null;
    }

    /**
     * @return Collection<int, array{
     *   line_key: string,
     *   product: Product,
     *   variant: ProductVariant|null,
     *   variant_label: string|null,
     *   qty: int,
     *   unit_label: string,
     *   unit_price: float,
     *   subtotal: float
     * }>
     */
    public function items(): Collection
    {
        if ($this->resolvedItems !== null) {
            return $this->resolvedItems;
        }

        $raw = $this->rawItems();
        if ($raw === []) {
            $this->resolvedItems = collect();

            return $this->resolvedItems;
        }

        $ids = collect($raw)
            ->map(fn (array $item) => (int) ($item['product_id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $variantIds = collect($raw)
            ->map(fn (array $item) => isset($item['variant_id']) ? (int) $item['variant_id'] : null)
            ->filter(fn (?int $id) => $id !== null && $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            $this->resolvedItems = collect();

            return $this->resolvedItems;
        }

        $products = Product::active()
            ->whereIn('id', $ids)
            ->with([
                'primaryPhoto',
                'photos',
                'variants' => fn ($query) => $query
                    ->active()
                    ->with('attributeValue.attribute'),
            ])
            ->get()
            ->keyBy('id');

        $variants = $products->flatMap->variants->keyBy('id');

        $this->resolvedItems = collect($raw)
            ->map(function (array $item, string $lineKey) use ($products, $variants) {
                $product = $products->get((int) $item['product_id']);

                if (! $product) {
                    return null;
                }

                $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
                $variant = null;
                $unitPrice = (float) $product->price;

                if ($variantId !== null) {
                    $variant = $variants->get($variantId);

                    if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                        return null;
                    }

                    $unitPrice = (float) $variant->price;
                } elseif ($product->hasConfigurableVariants()) {
                    return null;
                }

                $qty = (int) $item['qty'];
                $attributeName = $variant?->attributeValue?->attribute?->name;
                $valueName = $variant?->attributeValue?->value;
                $variantLabel = ($attributeName && $valueName)
                    ? $attributeName.': '.$valueName
                    : $valueName;

                return [
                    'line_key' => $lineKey,
                    'product' => $product,
                    'variant' => $variant,
                    'variant_label' => $variantLabel,
                    'qty' => $qty,
                    'unit_label' => $item['unit_label'] ?? 'unidades',
                    'unit_price' => $unitPrice,
                    'subtotal' => $qty * $unitPrice,
                ];
            })
            ->filter()
            ->values();

        return $this->resolvedItems;
    }

    public function total(): float
    {
        return (float) $this->items()->sum('subtotal');
    }

    public function count(): int
    {
        $totalQty = (int) $this->items()->sum(fn (array $item) => (int) $item['qty']);

        return max(0, $totalQty);
    }

    public function sessionCount(): int
    {
        $totalQty = 0;

        foreach ($this->rawItems() as $item) {
            $qty = isset($item['qty']) ? (int) $item['qty'] : 0;
            if ($qty > 0) {
                $totalQty += $qty;
            }
        }

        return max(0, $totalQty);
    }

    /**
     * @return array<string, array{product_id:int, variant_id:int|null, qty:int, unit_label:string}>
     */
    private function rawItems(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    private function buildLineKey(int $productId, ?int $variantId): string
    {
        return $productId.'-'.($variantId ?? 0);
    }
}
