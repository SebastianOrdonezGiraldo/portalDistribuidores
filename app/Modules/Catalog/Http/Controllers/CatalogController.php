<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\ProductSearchRequest;
use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(
        ProductSearchRequest $request,
        SearchEngineInterface $searchEngine,
        CategoryTreeQuery $categoryTreeQuery,
    ): View {
        $searchQuery = ProductSearchQuery::fromArray($request->validated());
        $products = $searchEngine->search($searchQuery);
        $categories = $categoryTreeQuery->execute();

        return view('catalog.index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $searchQuery,
        ]);
    }
}
