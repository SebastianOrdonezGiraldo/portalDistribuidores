<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = \App\Modules\Catalog\Models\ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id'                => Product::factory(),
            'product_attribute_value_id' => ProductAttributeValue::factory(),
            'price'                     => fake()->numberBetween(1000, 100000),
            'stock'                     => fake()->numberBetween(0, 200),
            'is_active'                 => true,
            'sort_order'                => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn () => ['product_id' => $product->id]);
    }
}
