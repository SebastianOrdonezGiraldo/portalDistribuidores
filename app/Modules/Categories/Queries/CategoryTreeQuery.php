<?php

namespace App\Modules\Categories\Queries;

use App\Modules\Categories\Models\Category;
use Illuminate\Support\Collection;

class CategoryTreeQuery
{
    public function execute(bool $activeOnly = true, array $filters = []): Collection
    {
        $categories = Category::query()
            ->with('synonyms')
            ->withCount(['products', 'synonyms'])
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $term = trim((string) $filters['q']);

                $query->where(function ($subQuery) use ($term) {
                    $subQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhereHas('synonyms', fn ($synonyms) => $synonyms->where('term', 'like', "%{$term}%"));
                });
            })
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                if ($filters['status'] === 'active') {
                    $query->where('is_active', true);
                } elseif ($filters['status'] === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when(! empty($filters['with_products']), function ($query) use ($filters) {
                if ($filters['with_products'] === 'yes') {
                    $query->has('products');
                } elseif ($filters['with_products'] === 'no') {
                    $query->doesntHave('products');
                }
            })
            ->when(($filters['sort'] ?? 'tree') === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when(($filters['sort'] ?? 'tree') === 'name_desc', fn ($query) => $query->orderByDesc('name'))
            ->when(($filters['sort'] ?? 'tree') === 'updated_desc', fn ($query) => $query->orderByDesc('updated_at'))
            ->when(($filters['sort'] ?? 'tree') === 'updated_asc', fn ($query) => $query->orderBy('updated_at'))
            ->when(($filters['sort'] ?? 'tree') === 'tree', fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
            ->get();

        $grouped = $categories->groupBy('parent_id');
        $existingIds = $categories->pluck('id')->all();
        $rootNodes = $categories->filter(fn (Category $category) => $category->parent_id === null || ! in_array($category->parent_id, $existingIds, true));

        $buildTree = function (Collection $nodes, int $depth = 0) use (&$buildTree, $grouped): Collection {
            return $nodes->map(function (Category $category) use (&$buildTree, $grouped, $depth) {
                $children = $grouped->get($category->id, collect());

                $category->setAttribute('depth', $depth);
                $category->setRelation('children', $buildTree($children, $depth + 1));

                return $category;
            });
        };

        return $buildTree($rootNodes->values());
    }
}
