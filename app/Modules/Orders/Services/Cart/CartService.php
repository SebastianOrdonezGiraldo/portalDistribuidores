<?php

namespace App\Modules\Orders\Services\Cart;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Models\Cart;
use App\Modules\Orders\Models\CartItem;
use App\Modules\Orders\Pricing\OrderPricingCalculator;
use App\Modules\Orders\Pricing\OrderPricingLine;
use App\Modules\Orders\Pricing\OrderPricingResult;
use App\Modules\Orders\Support\OrderLineVat;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Cart facade used by catalog, checkout and order creation.
 *
 * Distributor carts are persisted by account and referenced from the current
 * session so they remain available across devices and logins. Guest/admin carts
 * retain the legacy session storage. Raw rows intentionally store identifiers
 * and quantities; prices and availability are resolved from current catalog data.
 */
class CartService
{
    private const SESSION_KEY = 'orders.cart.items';

    private const CART_REFERENCE_SESSION_KEY = 'orders.cart.id';

    private ?Collection $resolvedItems = null;

    private ?OrderPricingResult $pricingResult = null;

    public function __construct(
        private readonly OrderPricingCalculator $orderPricingCalculator,
    ) {}

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
        if ($this->usesPersistentCart()) {
            $this->addToPersistentCart($product, $qty, $unitLabel, $variant);

            return;
        }

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
        $this->forgetResolved();
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
        if ($this->usesPersistentCart()) {
            $cart = $this->persistentCart(create: true);

            if (! $cart) {
                return;
            }

            DB::transaction(function () use ($cart, $quantities): void {
                $lockedCart = Cart::query()->lockForUpdate()->find($cart->id);

                if (! $lockedCart) {
                    return;
                }

                $items = $this->applyQuantities($this->rawItemsForCart($lockedCart), $quantities);
                $this->replacePersistentItems($lockedCart, $items);
                $this->markCartMutated($lockedCart);
            });

            $this->forgetResolved();

            return;
        }

        $items = $this->applyQuantities($this->rawItems(), $quantities);

        Session::put(self::SESSION_KEY, $items);
        $this->forgetResolved();
    }

    /**
     * @param  array<string, array{product_id:int, variant_id:int|null, qty:int, unit_label:string}>  $items
     * @param  array<int|string, mixed>  $quantities
     * @return array<string, array{product_id:int, variant_id:int|null, qty:int, unit_label:string}>
     */
    private function applyQuantities(array $items, array $quantities): array
    {
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

        return $items;
    }

    public function remove(string $lineKey): void
    {
        if ($this->usesPersistentCart()) {
            $cart = $this->persistentCart();

            if ($cart) {
                DB::transaction(function () use ($cart, $lineKey): void {
                    $lockedCart = Cart::query()->lockForUpdate()->find($cart->id);

                    if (! $lockedCart) {
                        return;
                    }

                    $deleted = $lockedCart->items()->where('line_key', $lineKey)->delete();

                    if ($deleted > 0) {
                        $this->markCartMutated($lockedCart);
                    }
                });
            }

            $this->forgetResolved();

            return;
        }

        $items = $this->rawItems();
        unset($items[$lineKey]);
        Session::put(self::SESSION_KEY, $items);
        $this->forgetResolved();
    }

    public function clear(): void
    {
        if ($this->usesPersistentCart()) {
            $cart = $this->persistentCart();
            $cart?->delete();
            Session::forget(self::CART_REFERENCE_SESSION_KEY);
        }

        Session::forget(self::SESSION_KEY);
        $this->forgetResolved();
    }

    /**
     * Resolve the session cart into display/order-ready rows.
     *
     * Stale rows for inactive products, mismatched variants or configurable
     * products without a chosen variant are filtered out instead of being
     * repaired here. Checkout and order creation consume this resolved shape.
     * Effective prices come from OrderPricingCalculator (gold threshold + minimums).
     *
     * @return Collection<int, array{
     *   line_key: string,
     *   product: Product,
     *   variant: ProductVariant|null,
     *   variant_label: string|null,
     *   qty: int,
     *   unit_label: string,
     *   tier: DistributorTier,
     *   base_unit_price: float,
     *   silver_unit_price: float,
     *   unit_price: float,
     *   unit_savings: float,
     *   vat_label: string,
     *   is_vat_excluded: bool,
     *   subtotal: float,
     *   line_savings: float,
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
            $this->pricingResult = null;

            return $this->resolvedItems;
        }

        $ids = collect($raw)
            ->map(fn (array $item) => (int) ($item['product_id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            $this->resolvedItems = collect();
            $this->pricingResult = null;

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
        $user = Auth::user();
        $distributorTier = $user?->distributor?->tier;
        $displayTier = $distributorTier ?? DistributorTier::Silver;

        $calculatorInput = [];
        $metaByIndex = [];

        foreach ($raw as $lineKey => $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                continue;
            }

            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            $variant = null;

            if ($variantId !== null) {
                $variant = $variants->get($variantId);

                if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                    continue;
                }
            } elseif ($product->hasConfigurableVariants()) {
                continue;
            }

            $metaByIndex[] = [
                'line_key' => (string) $lineKey,
                'unit_label' => $item['unit_label'] ?? 'unidades',
                'available_qty' => $this->resolveStockLimit($product, $variant),
            ];
            $calculatorInput[] = [
                'product' => $product,
                'variant' => $variant,
                'qty' => (int) $item['qty'],
                'unit_label' => $item['unit_label'] ?? 'unidades',
            ];
        }

        if ($calculatorInput === []) {
            $this->resolvedItems = collect();
            $this->pricingResult = null;

            return $this->resolvedItems;
        }

        $this->pricingResult = $this->orderPricingCalculator->calculate($distributorTier, $calculatorInput);

        $this->resolvedItems = $this->pricingResult->lines
            ->values()
            ->map(function (OrderPricingLine $line, int $index) use ($metaByIndex, $displayTier) {
                $meta = $metaByIndex[$index];
                $product = $line->product;
                $variant = $line->variant;
                $attributeName = $variant?->attributeValue?->attribute?->name;
                $valueName = $variant?->attributeValue?->value;
                $variantLabel = ($attributeName && $valueName)
                    ? $attributeName.': '.$valueName
                    : $valueName;
                $isVatExcluded = (bool) $product->is_vat_excluded;
                $unitPrice = (float) $line->effectiveUnitPriceDecimal();
                $unitSavings = (float) $line->unitSavingsDecimal();

                return [
                    'line_key' => $meta['line_key'],
                    'product' => $product,
                    'variant' => $variant,
                    'variant_label' => $variantLabel,
                    'qty' => $line->qty,
                    'unit_label' => $line->unitLabel,
                    'tier' => $displayTier,
                    'base_unit_price' => (float) $line->goldUnitPriceDecimal(),
                    'silver_unit_price' => (float) $line->silverUnitPriceDecimal(),
                    'unit_price' => $unitPrice,
                    'unit_savings' => $unitSavings,
                    'vat_label' => OrderLineVat::label($isVatExcluded),
                    'is_vat_excluded' => $isVatExcluded,
                    'subtotal' => (float) $line->effectiveSubtotalDecimal(),
                    'line_savings' => (float) $line->lineSavingsDecimal(),
                    'available_qty' => $meta['available_qty'],
                ];
            });

        return $this->resolvedItems;
    }

    public function pricingResult(): ?OrderPricingResult
    {
        $this->items();

        return $this->pricingResult;
    }

    public function total(): float
    {
        $pricing = $this->pricingResult();

        if ($pricing !== null) {
            return (float) $pricing->effectiveTotalDecimal();
        }

        return (float) $this->items()->sum('subtotal');
    }

    private function forgetResolved(): void
    {
        $this->resolvedItems = null;
        $this->pricingResult = null;
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
        if ($this->usesPersistentCart()) {
            $cart = $this->persistentCart();

            return $cart ? $this->rawItemsForCart($cart) : [];
        }

        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * @return array<string, array{product_id:int, variant_id:int|null, qty:int, unit_label:string}>
     */
    private function rawItemsForCart(Cart $cart): array
    {
        return $cart->items()
            ->get(['line_key', 'product_id', 'product_variant_id', 'qty', 'unit_label'])
            ->mapWithKeys(fn (CartItem $item): array => [
                $item->line_key => [
                    'product_id' => (int) $item->product_id,
                    'variant_id' => $item->product_variant_id !== null ? (int) $item->product_variant_id : null,
                    'qty' => (int) $item->qty,
                    'unit_label' => $item->unit_label,
                ],
            ])
            ->all();
    }

    private function usesPersistentCart(): bool
    {
        return Auth::user()?->isDistributor() === true;
    }

    private function persistentCart(bool $create = false): ?Cart
    {
        $user = Auth::user();

        if (! $user?->isDistributor()) {
            return null;
        }

        $reference = (int) Session::get(self::CART_REFERENCE_SESSION_KEY, 0);
        $cart = $reference > 0
            ? Cart::query()->whereKey($reference)->where('user_id', $user->id)->first()
            : null;

        $cart ??= Cart::query()->where('user_id', $user->id)->first();

        if ($cart?->hasExpired()) {
            $cart->delete();
            Session::forget(self::CART_REFERENCE_SESSION_KEY);
            $cart = null;
        }

        if (! $cart && $create) {
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        }

        if ($cart) {
            Session::put(self::CART_REFERENCE_SESSION_KEY, $cart->id);
        }

        return $cart;
    }

    private function addToPersistentCart(Product $product, int $qty, string $unitLabel, ?ProductVariant $variant): void
    {
        $cart = $this->persistentCart(create: true);

        if (! $cart) {
            return;
        }

        DB::transaction(function () use ($cart, $product, $qty, $unitLabel, $variant): void {
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
            $lineKey = $this->buildLineKey($product->id, $variant?->id);
            $item = $lockedCart->items()->where('line_key', $lineKey)->first();
            $requestedQty = max(1, (int) ($item?->qty ?? 0) + $qty);
            $availableQty = $this->resolveStockLimit($product, $variant);

            if ($availableQty !== null && $requestedQty > $availableQty) {
                throw new DomainException($this->stockExceededMessage($availableQty));
            }

            $lockedCart->items()->updateOrCreate(
                ['line_key' => $lineKey],
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'qty' => $requestedQty,
                    'unit_label' => $unitLabel,
                ],
            );
            $this->markCartMutated($lockedCart);
        });

        Session::forget(self::SESSION_KEY);
        $this->forgetResolved();
    }

    /**
     * @param  array<string, array{product_id:int, variant_id:int|null, qty:int, unit_label:string}>  $items
     */
    private function replacePersistentItems(Cart $cart, array $items): void
    {
        $lineKeys = array_keys($items);

        if ($lineKeys === []) {
            $cart->items()->delete();
        } else {
            $cart->items()->whereNotIn('line_key', $lineKeys)->delete();
        }

        foreach ($items as $lineKey => $item) {
            $cart->items()->updateOrCreate(
                ['line_key' => $lineKey],
                [
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'qty' => $item['qty'],
                    'unit_label' => $item['unit_label'],
                ],
            );
        }
    }

    private function markCartMutated(Cart $cart): void
    {
        $timestamp = now();

        DB::table('carts')->where('id', $cart->id)->update([
            'reminder_sent_at' => null,
            'updated_at' => $timestamp,
        ]);

        $cart->reminder_sent_at = null;
        $cart->updated_at = $timestamp;
    }

    private function buildLineKey(int $productId, ?int $variantId): string
    {
        return $productId.'-'.($variantId ?? 0);
    }

    private function resolveStockLimit(?Product $product, ?ProductVariant $variant): ?int
    {
        if ($variant instanceof ProductVariant) {
            return $this->normalizeStockLimit($variant->available_stock);
        }

        return $this->normalizeStockLimit($product?->available_stock);
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
