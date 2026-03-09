<?php

namespace App\Modules\Categories\Actions;

use App\Modules\Categories\Models\Category;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public function execute(array $payload): Category
    {
        $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);

        return Category::create($payload);
    }
}

