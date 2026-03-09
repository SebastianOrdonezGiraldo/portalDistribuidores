<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class MeilisearchSearchEngine implements SearchEngineInterface
{
    public function search(ProductSearchQuery $query): LengthAwarePaginator
    {
        return new Paginator(
            items: [],
            total: 0,
            perPage: $query->perPage,
            currentPage: $query->page,
            options: ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}

