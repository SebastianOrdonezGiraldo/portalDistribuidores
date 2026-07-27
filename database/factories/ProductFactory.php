<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'brand' => fake()->company(),
            'sku' => 'SKU-'.fake()->unique()->numerify('######'),
            'description' => fake()->sentence(),
            'category_id' => Category::factory(),
            'price' => fake()->numberBetween(1000, 100000),
            'stock' => fake()->numberBetween(1, 500),
            'is_active' => true,
            'is_new' => false,
            'new_until' => null,
            'is_vat_excluded' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn () => ['price' => $price]);
    }

    public function markedAsNew(?string $until = null): static
    {
        return $this->state(fn () => [
            'is_new' => true,
            'new_until' => $until,
        ]);
    }
}
