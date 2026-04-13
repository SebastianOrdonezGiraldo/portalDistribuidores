<?php

namespace Database\Factories;

use App\Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
