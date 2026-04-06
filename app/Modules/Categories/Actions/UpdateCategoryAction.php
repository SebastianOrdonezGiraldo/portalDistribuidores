<?php

namespace App\Modules\Categories\Actions;

use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UpdateCategoryAction
{
    public function execute(Category $category, array $payload): Category
    {
        $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);
        $category->update($payload);

        Cache::forget('footer_top_categories');
        CategoryTreeQuery::forgetCatalogCache();

        return $category->refresh();
    }
}
