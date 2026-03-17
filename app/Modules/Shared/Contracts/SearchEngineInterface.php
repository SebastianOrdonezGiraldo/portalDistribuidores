<?php

namespace App\Modules\Shared\Contracts;

use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SearchEngineInterface
{
    public function search(ProductSearchQuery $query): LengthAwarePaginator;
}

