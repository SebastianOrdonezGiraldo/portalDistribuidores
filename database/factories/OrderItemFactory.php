<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 10);
        $priceEach = fake()->numberBetween(1000, 50000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'product_name_snapshot' => fake()->words(3, true),
            'sku_snapshot' => 'SKU-'.fake()->numerify('######'),
            'variant_attribute_snapshot' => null,
            'variant_value_snapshot' => null,
            'qty' => $qty,
            'unit_label' => 'unidades',
            'price_each' => $priceEach,
            'subtotal' => $qty * $priceEach,
            'is_vat_excluded_snapshot' => false,
            'vat_rate_snapshot' => '0.1300',
        ];
    }
}
