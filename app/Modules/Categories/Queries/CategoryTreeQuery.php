<?php

namespace App\Modules\Categories\Queries;

use App\Modules\Categories\Models\Category;
use Illuminate\Support\Collection;

class CategoryTreeQuery
{
    public function execute(bool $activeOnly = true): Collection
    {
        $categories = Category::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $grouped = $categories->groupBy('parent_id');

        $buildTree = function (?int $parentId) use (&$buildTree, $grouped): Collection {
            $nodes = $grouped->get($parentId, collect());

            return $nodes->map(function (Category $category) use (&$buildTree) {
                $category->setRelation('children', $buildTree($category->id));

                return $category;
            });
        };

        return $buildTree(null);
    }
}

