<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\ProductSearchRequest;
use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(
        ProductSearchRequest $request,
        SearchEngineInterface $searchEngine,
        CategoryTreeQuery $categoryTreeQuery,
    ): View|JsonResponse {
        $searchQuery = ProductSearchQuery::fromArray($request->validated());
        $products = $searchEngine->search($searchQuery);

        if ($request->ajax()) {
            return response()->json([
                'html'     => view('catalog._products-partial', compact('products'))->render(),
                'hasMore'  => $products->hasMorePages(),
                'nextPage' => $products->currentPage() + 1,
            ]);
        }

        $categories = $categoryTreeQuery->execute();

        return view('catalog.index', [
            'products'   => $products,
            'categories' => $categories,
            'search'     => $searchQuery,
        ]);
    }
}
