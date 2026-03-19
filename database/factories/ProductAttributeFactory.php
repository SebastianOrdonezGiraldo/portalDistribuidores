<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Catalog\Models\ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    protected $model = \App\Modules\Catalog\Models\ProductAttribute::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
        ];
    }
}
