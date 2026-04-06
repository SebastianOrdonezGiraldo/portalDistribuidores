<?php

namespace Database\Factories;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = \App\Modules\Catalog\Models\Product::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->words(3, true),
            'brand'       => fake()->company(),
            'sku'         => 'SKU-'.fake()->unique()->numerify('######'),
            'description' => fake()->sentence(),
            'category_id' => Category::factory(),
            'price'       => fake()->numberBetween(1000, 100000),
            'stock'       => fake()->numberBetween(1, 500),
            'is_active'   => true,
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
}
