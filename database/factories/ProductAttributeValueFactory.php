<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductAttributeValue>
 */
class ProductAttributeValueFactory extends Factory
{
    protected $model = ProductAttributeValue::class;

    public function definition(): array
    {
        $value = fake()->unique()->word();

        return [
            'product_attribute_id' => ProductAttribute::factory(),
            'value' => $value,
            'slug' => Str::slug($value).'-'.Str::random(4),
        ];
    }
}
