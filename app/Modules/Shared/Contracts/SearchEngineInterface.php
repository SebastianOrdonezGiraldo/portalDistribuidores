<?php

namespace App\Modules\Shared\Contracts;

use App\Modules\Catalog\Queries\ProductSearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchEngineInterface
{
    public function search(ProductSearchQuery $query): LengthAwarePaginator;
}

