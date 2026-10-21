<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function configure(): static
    {
        return $this->afterMaking(function (ProductVariant $variant): void {
            if ($variant->silver_price !== null) {
                return;
            }

            $priceCents = (int) round(((float) $variant->price) * 100);
            $legacySilverCents = intdiv(($priceCents * 10_500) + 9_999, 10_000);
            $legacySilverCents = intdiv($legacySilverCents + 99_999, 100_000) * 100_000;
            $variant->silver_price = $legacySilverCents / 100;
        });
    }

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_attribute_value_id' => ProductAttributeValue::factory(),
            'price' => fake()->numberBetween(1000, 100000),
            'stock' => fake()->numberBetween(0, 200),
            'is_active' => true,
            'sort_order' => 0,
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
