<?php

namespace App\Modules\Categories\Queries;

use App\Modules\Categories\Models\Category;
use Illuminate\Support\Collection;

class CategoryBreadcrumbsQuery
{
    public function execute(Category $category): Collection
    {
        $breadcrumbs = collect();
        $current = $category->loadMissing('parent');

        while ($current) {
            $breadcrumbs->prepend($current);
            $current = $current->parent?->loadMissing('parent');
        }

        return $breadcrumbs;
    }
}

