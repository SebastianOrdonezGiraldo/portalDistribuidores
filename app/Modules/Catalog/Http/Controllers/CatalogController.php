<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\ProductSearchRequest;
use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            return response()->json($this->buildProductListPayload($products));
        }

        $categories = $categoryTreeQuery->execute();

        return view('catalog.index', [
            'products'   => $products,
            'categories' => $categories,
            'search'     => $searchQuery,
            'canonicalUrl' => $this->canonicalUrl($searchQuery),
            'robotsContent' => $this->robotsContent($searchQuery),
        ]);
    }

    private function buildProductListPayload(LengthAwarePaginator $products): array
    {
        $listKey = 'catalog';

        return [
            'html' => view('catalog._products-partial', compact('products', 'listKey'))->render(),
            'controlsHtml' => view('catalog._product-list-controls', compact('products'))->render(),
            'hasMore' => $products->hasMorePages(),
            'currentPage' => $products->currentPage(),
            'nextPage' => $products->currentPage() + 1,
            'pushUrl' => $this->pushUrl($products),
        ];
    }

    private function canonicalUrl(ProductSearchQuery $searchQuery): string
    {
        $query = [];

        if (filled($searchQuery->term)) {
            $query['term'] = $searchQuery->term;
        }

        if ($searchQuery->categoryId !== null) {
            $query['category_id'] = $searchQuery->categoryId;
        }

        if ($searchQuery->categoryId !== null && ! $searchQuery->includeChildren) {
            $query['include_children'] = 0;
        }

        return route('catalog.index', $query);
    }

    private function robotsContent(ProductSearchQuery $searchQuery): string
    {
        $isBaseCatalog = blank($searchQuery->term)
            && $searchQuery->categoryId === null
            && $searchQuery->page === 1;

        return $isBaseCatalog ? 'index,follow' : 'noindex,follow';
    }

    private function pushUrl(LengthAwarePaginator $products): string
    {
        $query = request()->except('list');

        if ($products->currentPage() <= 1) {
            unset($query[$products->getPageName()]);
        } else {
            $query[$products->getPageName()] = $products->currentPage();
        }

        $query = array_filter(
            $query,
            static fn ($value) => ! is_null($value) && $value !== ''
        );

        $queryString = http_build_query($query);

        return $queryString === ''
            ? request()->url()
            : request()->url() . '?' . $queryString;
    }
}
