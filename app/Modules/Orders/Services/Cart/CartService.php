<?php

namespace App\Modules\Orders\Services\Cart;

use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'orders.cart.items';

    public function add(Product $product, int $qty = 1, string $unitLabel = 'unidad'): void
    {
        $items = $this->rawItems();
        $productId = (string) $product->id;

        if (! isset($items[$productId])) {
            $items[$productId] = [
                'product_id' => $product->id,
                'qty' => 0,
                'unit_label' => $unitLabel,
            ];
        }

        $items[$productId]['qty'] = max(1, (int) $items[$productId]['qty'] + $qty);
        $items[$productId]['unit_label'] = $unitLabel;

        Session::put(self::SESSION_KEY, $items);
    }

    /**
     * @param array<int|string, mixed> $quantities
     */
    public function update(array $quantities): void
    {
        $items = $this->rawItems();

        foreach ($quantities as $productId => $qty) {
            $key = (string) $productId;

            if (! isset($items[$key])) {
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
    }

    public function remove(int $productId): void
    {
        $items = $this->rawItems();
        unset($items[(string) $productId]);
        Session::put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * @return Collection<int, array{
     *   product: Product,
     *   qty: int,
     *   unit_label: string,
     *   subtotal: float
     * }>
     */
    public function items(): Collection
    {
        $raw = $this->rawItems();
        $ids = array_map(fn (array $item) => (int) $item['product_id'], array_values($raw));
        $products = Product::query()->whereIn('id', $ids)->where('is_active', true)->get()->keyBy('id');

        return collect($raw)
            ->map(function (array $item) use ($products) {
                $product = $products->get((int) $item['product_id']);

                if (! $product) {
                    return null;
                }

                $qty = (int) $item['qty'];

                return [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_label' => $item['unit_label'] ?? 'unidad',
                    'subtotal' => $qty * (float) $product->price,
                ];
            })
            ->filter()
            ->values();
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

    /**
     * @return array<string, array{product_id:int, qty:int, unit_label:string}>
     */
    private function rawItems(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }
}
