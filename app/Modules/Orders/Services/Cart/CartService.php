<?php

namespace App\Modules\Orders\Services\Cart;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Session-backed cart facade used by catalog, checkout and order creation.
 *
 * Raw cart rows intentionally store only identifiers and quantities. The public
 * item read model resolves active products, active variants, current prices,
 * VAT labels and stock limits each time the cache is invalidated.
 */
class CartService
{
    private const SESSION_KEY = 'orders.cart.items';

    private ?Collection $resolvedItems = null;

    /**
     * Add a product or variant line, merging with the existing session line.
     *
     * A null stock value means "unbounded/manual unknown" and does not block the
     * cart. Numeric stock is enforced before the session is updated.
     *
     * @throws DomainException when the requested quantity exceeds known stock
     */
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

        $requestedQty = max(1, (int) $items[$lineKey]['qty'] + $qty);
        $availableQty = $this->resolveStockLimit($product, $variant);

        if ($availableQty !== null && $requestedQty > $availableQty) {
            throw new DomainException($this->stockExceededMessage($availableQty));
        }

        $items[$lineKey]['qty'] = $requestedQty;
        $items[$lineKey]['unit_label'] = $unitLabel;

        Session::put(self::SESSION_KEY, $items);
        $this->resolvedItems = null;
    }

    /**
     * Apply submitted quantities to existing cart lines.
     *
     * Missing line keys are ignored so stale forms cannot create new cart lines.
     * Non-positive values remove the line; positive values are stock-checked
     * against the current product/variant records before mutation.
     *
     * @param  array<int|string, mixed>  $quantities
     *
     * @throws DomainException when a positive quantity exceeds known stock
     */
    public function update(array $quantities): void
    {
        $items = $this->rawItems();
        $requestedLineKeys = collect($quantities)
            ->keys()
            ->map(fn ($lineKey) => (string) $lineKey)
            ->filter(fn (string $lineKey) => array_key_exists($lineKey, $items))
            ->values();

        if ($requestedLineKeys->isNotEmpty()) {
            $requestedItems = $requestedLineKeys
                ->mapWithKeys(fn (string $lineKey) => [$lineKey => $items[$lineKey]]);

            $productIds = $requestedItems
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $variantIds = $requestedItems
                ->pluck('variant_id')
                ->filter(fn ($id) => $id !== null)
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $products = $productIds === []
                ? collect()
                : Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

            $variants = $variantIds === []
                ? collect()
                : ProductVariant::query()->whereIn('id', $variantIds)->get()->keyBy('id');

            foreach ($requestedItems as $lineKey => $item) {
                $value = (int) ($quantities[$lineKey] ?? 0);

                if ($value <= 0) {
                    continue;
                }

                $stockLimit = $this->resolveStockLimitForLineItem($item, $products, $variants);

                if ($stockLimit !== null && $value > $stockLimit) {
                    throw new DomainException($this->stockExceededMessage($stockLimit));
                }
            }
        }

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
     * Resolve the session cart into display/order-ready rows.
     *
     * Stale rows for inactive products, mismatched variants or configurable
     * products without a chosen variant are filtered out instead of being
     * repaired here. Checkout and order creation consume this resolved shape.
     *
     * @return Collection<int, array{
     *   line_key: string,
     *   product: Product,
     *   variant: ProductVariant|null,
     *   variant_label: string|null,
     *   qty: int,
     *   unit_label: string,
     *   unit_price: float,
     *   vat_label: string,
     *   is_vat_excluded: bool,
     *   subtotal: float,
     *   available_qty: int|null
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
                $availableQty = $this->resolveStockLimit($product, $variant);
                $isVatExcluded = (bool) $product->is_vat_excluded;

                return [
                    'line_key' => $lineKey,
                    'product' => $product,
                    'variant' => $variant,
                    'variant_label' => $variantLabel,
                    'qty' => $qty,
                    'unit_label' => $item['unit_label'] ?? 'unidades',
                    'unit_price' => $unitPrice,
                    'vat_label' => OrderLineVat::label($isVatExcluded),
                    'is_vat_excluded' => $isVatExcluded,
                    'subtotal' => $qty * $unitPrice,
                    'available_qty' => $availableQty,
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

    private function resolveStockLimit(?Product $product, ?ProductVariant $variant): ?int
    {
        if ($variant instanceof ProductVariant) {
            return $this->normalizeStockLimit($variant->stock);
        }

        return $this->normalizeStockLimit($product?->stock);
    }

    /**
     * Resolve stock for a raw cart line using already-loaded products/variants.
     *
     * Returning null is deliberate for missing or mismatched records because the
     * caller is only validating quantities for known lines; final item
     * resolution filters invalid rows.
     *
     * @param  array<string, mixed>  $item
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function resolveStockLimitForLineItem(array $item, Collection $products, Collection $variants): ?int
    {
        $productId = (int) ($item['product_id'] ?? 0);
        $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;

        $product = $products->get($productId);
        $variant = $variantId !== null ? $variants->get($variantId) : null;

        if ($variant instanceof ProductVariant && (int) $variant->product_id !== $productId) {
            return null;
        }

        return $this->resolveStockLimit($product, $variant);
    }

    private function normalizeStockLimit(mixed $stock): ?int
    {
        if (! is_numeric($stock)) {
            return null;
        }

        return max(0, (int) floor((float) $stock));
    }

    private function stockExceededMessage(int $stockLimit): string
    {
        $unitWord = $stockLimit === 1 ? 'unidad' : 'unidades';

        return "Solo hay {$stockLimit} {$unitWord} disponibles para este producto.";
    }
}
