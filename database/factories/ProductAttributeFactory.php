<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
        ];
    }
}
