<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Orders\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = \App\Modules\Orders\Models\OrderItem::class;

    public function definition(): array
    {
        $qty        = fake()->numberBetween(1, 10);
        $priceEach  = fake()->numberBetween(1000, 50000);

        return [
            'order_id'                   => Order::factory(),
            'product_id'                 => Product::factory(),
            'product_variant_id'         => null,
            'product_name_snapshot'      => fake()->words(3, true),
            'sku_snapshot'               => 'SKU-'.fake()->numerify('######'),
            'variant_attribute_snapshot' => null,
            'variant_value_snapshot'     => null,
            'qty'                        => $qty,
            'unit_label'                 => 'unidades',
            'price_each'                 => $priceEach,
            'subtotal'                   => $qty * $priceEach,
        ];
    }
}
