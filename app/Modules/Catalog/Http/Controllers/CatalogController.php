<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AddServerTiming;
use App\Modules\Catalog\Http\Requests\ProductSearchRequest;
use App\Modules\Categories\Queries\CategoryTreeQuery;
use App\Modules\Orders\Pricing\DistributorTierMetricsService;
use App\Modules\Orders\Pricing\DistributorTierResolver;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Consultar catalogo publico.
     *
     * Devuelve HTML completo o JSON parcial cuando la solicitud es AJAX.
     *
     * @group Catalogo publico
     *
     * @unauthenticated
     *
     * @queryParam term string Texto de busqueda. Example: guantes
     * @queryParam category_id integer ID de categoria. Example: 3
     * @queryParam include_children boolean Incluye subcategorias. Example: true
     * @queryParam sort string Orden: relevance, newest, price_asc, price_desc. Example: relevance
     * @queryParam page integer Pagina. Example: 1
     * @queryParam per_page integer Resultados por pagina, maximo 50. Example: 20
     *
     * @response 200 {"content":"Vista HTML del catalogo o payload JSON AJAX"}
     * @response 422 {"message":"Filtros invalidos"}
     * @response 429 {"message":"Has realizado demasiados intentos."}
     */
    public function __invoke(
        ProductSearchRequest $request,
        SearchEngineInterface $searchEngine,
        CategoryTreeQuery $categoryTreeQuery,
        DistributorTierResolver $tierResolver,
        DistributorTierMetricsService $tierMetricsService,
    ): View|JsonResponse {
        $controllerStartedAt = microtime(true);
        $searchQuery = ProductSearchQuery::fromArray($request->validated());
        $products = $searchEngine->search($searchQuery);

        $user = $request->user();
        $tier = $tierResolver->resolve($user);
        $isDistributor = (bool) $user?->isDistributor();
        $distributor = $user?->distributor;
        $tierMetrics = ($isDistributor && $distributor)
            ? $tierMetricsService->forDistributor($distributor)
            : null;

        if ($request->ajax()) {
            AddServerTiming::addMetric(
                $request,
                'catalog_controller',
                (microtime(true) - $controllerStartedAt) * 1000,
                'Catalog controller total'
            );

            return response()->json($this->buildProductListPayload($products, $searchQuery, $tier, $isDistributor));
        }

        $treeStartedAt = microtime(true);
        $categories = $categoryTreeQuery->execute();

        AddServerTiming::addMetric(
            $request,
            'catalog_category_tree',
            (microtime(true) - $treeStartedAt) * 1000,
            'Category tree query'
        );
        AddServerTiming::addMetric(
            $request,
            'catalog_controller',
            (microtime(true) - $controllerStartedAt) * 1000,
            'Catalog controller total'
        );

        return view('catalog.index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $searchQuery,
            'canonicalUrl' => $this->canonicalUrl($searchQuery),
            'robotsContent' => $this->robotsContent($searchQuery),
            'distributorTier' => $tier,
            'showTierExperience' => $isDistributor,
            'tierMetrics' => $tierMetrics,
        ]);
    }

    private function buildProductListPayload(
        LengthAwarePaginator $products,
        ProductSearchQuery $searchQuery,
        DistributorTier $tier,
        bool $isDistributor,
    ): array {
        $listKey = 'catalog';
        $pricingMode = $isDistributor ? $tier->pricingMode() : 'single';

        return [
            'html' => view('catalog._products-partial', [
                'products' => $products,
                'listKey' => $listKey,
                'pricingMode' => $pricingMode,
                'tier' => $tier,
            ])->render(),
            'controlsHtml' => view('catalog._product-list-controls', compact('products'))->render(),
            'hasMore' => $products->hasMorePages(),
            'currentPage' => $products->currentPage(),
            'nextPage' => $products->currentPage() + 1,
            'pushUrl' => $this->pushUrl($products, $searchQuery),
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

        if ($searchQuery->sort !== ProductSearchQuery::SORT_RELEVANCE) {
            $query['sort'] = $searchQuery->sort;
        }

        if ($searchQuery->perPage !== 20) {
            $query['per_page'] = $searchQuery->perPage;
        }

        return route('catalog.index', $query);
    }

    private function robotsContent(ProductSearchQuery $searchQuery): string
    {
        $isBaseCatalog = blank($searchQuery->term)
            && $searchQuery->categoryId === null
            && $searchQuery->includeChildren
            && $searchQuery->sort === ProductSearchQuery::SORT_RELEVANCE
            && $searchQuery->perPage === 20
            && $searchQuery->page === 1;

        return $isBaseCatalog ? 'index,follow' : 'noindex,follow';
    }

    private function pushUrl(LengthAwarePaginator $products, ProductSearchQuery $searchQuery): string
    {
        $query = [];

        if (filled($searchQuery->term)) {
            $query['term'] = $searchQuery->term;
        }

        if ($searchQuery->categoryId !== null) {
            $query['category_id'] = $searchQuery->categoryId;
        }

        if (! $searchQuery->includeChildren) {
            $query['include_children'] = 0;
        }

        if ($searchQuery->sort !== ProductSearchQuery::SORT_RELEVANCE) {
            $query['sort'] = $searchQuery->sort;
        }

        if ($searchQuery->perPage !== 20) {
            $query['per_page'] = $searchQuery->perPage;
        }

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
            ? route('catalog.index')
            : route('catalog.index').'?'.$queryString;
    }
}
