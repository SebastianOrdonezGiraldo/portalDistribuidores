<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\ProductSearchRequest;
use App\Modules\Catalog\Queries\ProductSearchQuery;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(
        ProductSearchRequest $request,
        SearchEngineInterface $searchEngine,
        CategoryTreeQuery $categoryTreeQuery,
        CartService $cartService,
    ): View {
        $searchQuery = ProductSearchQuery::fromArray($request->validated());
        $products = $searchEngine->search($searchQuery);
        $categories = $categoryTreeQuery->execute();

        return view('catalog.index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $searchQuery,
            'cartCount' => $cartService->count(),
        ]);
    }
}
