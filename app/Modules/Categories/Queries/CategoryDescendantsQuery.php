<?php

namespace App\Modules\Categories\Queries;

use App\Modules\Categories\Models\Category;

class CategoryDescendantsQuery
{
    /**
     * @return list<int>
     */
    public function execute(int $categoryId): array
    {
        $childrenMap = Category::query()
            ->select(['id', 'parent_id'])
            ->get()
            ->groupBy('parent_id')
            ->map(fn ($items) => $items->pluck('id')->all())
            ->all();

        $collected = [];
        $stack = [$categoryId];

        while ($stack !== []) {
            $current = array_pop($stack);

            if (in_array($current, $collected, true)) {
                continue;
            }

            $collected[] = $current;

            foreach ($childrenMap[$current] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return $collected;
    }
}
