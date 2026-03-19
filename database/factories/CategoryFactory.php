<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Categories\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = \App\Modules\Categories\Models\Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id'  => null,
            'name'       => $name,
            'slug'       => Str::slug($name).'-'.Str::random(4),
            'is_active'  => true,
            'sort_order' => 0,
        ];
    }
}
