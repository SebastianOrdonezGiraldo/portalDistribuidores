<?php

namespace App\Modules\Catalog\Queries;

use App\Http\Middleware\AddServerTiming;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Queries\CategoryDescendantsQuery;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use App\Modules\Shared\Support\TextNormalizer;
use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PostgresSearchEngine implements SearchEngineInterface
{
    private const SCORE_NAME_EXACT      = 500;
    private const SCORE_NAME_STRONG     = 380;
    private const SCORE_BRAND_EXACT     = 340;
    private const SCORE_BRAND_STRONG    = 280;
    private const SCORE_CATEGORY_EXACT  = 260;
    private const SCORE_CATEGORY_STRONG = 200;
    private const SCORE_SYNONYM_EXACT   = 120;
    private const SCORE_SYNONYM_TOKEN   = 80;
    private const SCORE_DESCRIPTION     = 20;
    private const STRONG_MATCH_MIN_TOKENS = 2;

    public function __construct(
        private readonly CategoryDescendantsQuery $categoryDescendantsQuery,
    ) {}

    public function search(ProductSearchQuery $query): LengthAwarePaginator
    {
        $searchStartedAt = microtime(true);
        $normalizedTerm = $query->normalizedTerm();

        if ($normalizedTerm === '') {
            $dbStartedAt = microtime(true);
            $paginator = $this
                ->applySort($this->baseQuery($query), $query)
                ->with(['category', 'primaryPhoto', 'photos', 'variantAttribute', 'variants.attributeValue'])
                ->paginate($query->perPage, ['products.*'], 'page', $query->page);
            $paginator->appends($this->safePaginationQuery($query));
            $this->recordTiming('catalog_db', (microtime(true) - $dbStartedAt) * 1000, 'Catalog DB query');
            $this->recordTiming('catalog_search', (microtime(true) - $searchStartedAt) * 1000, 'Catalog search total');

            return $paginator;
        }

        // Pre-filtrar en SQL: solo traer productos que contengan al menos
        // un token en nombre, marca, descripción, categoría o sinónimos.
        // El scoring detallado (pesos, strong-match, etc.) se mantiene en PHP
        // pero solo se ejecuta sobre este subconjunto reducido de candidatos.
        $candidatesStartedAt = microtime(true);
        $candidates = $this
            ->baseQuery($query)
            ->leftJoin('categories as _fcat', 'products.category_id', '=', '_fcat.id')
            ->leftJoin('category_synonyms as _fsyn', '_fcat.id', '=', '_fsyn.category_id')
            ->where(function (Builder $q) use ($query): void {
                foreach ($query->tokens() as $token) {
                    $like = '%' . $token . '%';
                    $q->orWhereRaw($this->ciExpr('products.name') . ' LIKE ?',                        [$like])
                      ->orWhereRaw($this->ciExpr('coalesce(products.brand, \'\')') . ' LIKE ?',       [$like])
                      ->orWhereRaw($this->ciExpr('coalesce(products.description, \'\')') . ' LIKE ?', [$like])
                      ->orWhereRaw($this->ciExpr('coalesce(_fcat.name, \'\')') . ' LIKE ?',           [$like])
                      ->orWhereRaw($this->ciExpr('coalesce(_fsyn.term, \'\')') . ' LIKE ?',           [$like]);
                }
            })
            ->select('products.*')
            ->distinct()
            // Para ranking solo se requiere categoria/sinonimos.
            // Las relaciones pesadas (fotos/variantes) se cargan solo para la pagina actual.
            ->with(['category.synonyms'])
            ->get();
        $this->recordTiming('catalog_candidates', (microtime(true) - $candidatesStartedAt) * 1000, 'Search candidates query');

        $rankStartedAt = microtime(true);
        $ranked = $this->rank($candidates, $query);
        $this->recordTiming('catalog_rank', (microtime(true) - $rankStartedAt) * 1000, 'In-memory ranking');

        $total  = $ranked->count();
        $offset = ($query->page - 1) * $query->perPage;
        $hydrateStartedAt = microtime(true);
        $items  = $this->hydratePageItems(
            $ranked->slice($offset, $query->perPage)->values()
        );
        $this->recordTiming('catalog_hydrate', (microtime(true) - $hydrateStartedAt) * 1000, 'Hydrate current page');

        $paginator = new Paginator(
            $items,
            $total,
            $query->perPage,
            $query->page,
            ['path' => route('catalog.index'), 'query' => $this->safePaginationQuery($query)],
        );

        $this->recordTiming('catalog_search', (microtime(true) - $searchStartedAt) * 1000, 'Catalog search total');

        return $paginator;
    }

    private function hydratePageItems(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $ids = $items->pluck('id')->values();

        $productsById = Product::query()
            ->whereIn('id', $ids->all())
            ->with(['category', 'primaryPhoto', 'photos', 'variantAttribute', 'variants.attributeValue'])
            ->get()
            ->keyBy('id');

        return $ids
            ->map(static fn ($id) => $productsById->get($id))
            ->filter()
            ->values();
    }

    private function baseQuery(ProductSearchQuery $query): Builder
    {
        return Product::query()
            ->where('products.is_active', true)
            ->when($query->categoryId !== null, function (Builder $builder) use ($query): void {
                if ($query->includeChildren) {
                    $categoryIds = $this->categoryDescendantsQuery->execute($query->categoryId);
                    $builder->whereIn('products.category_id', $categoryIds);

                    return;
                }

                $builder->where('products.category_id', $query->categoryId);
            });
    }

    private function rank(Collection $products, ProductSearchQuery $query): Collection
    {
        $term    = $query->normalizedTerm();
        $tokens  = $query->tokens();
        $requiresStrongMatch = count($tokens) >= self::STRONG_MATCH_MIN_TOKENS;

        return $products
            ->map(function (Product $product) use ($term, $tokens, $requiresStrongMatch) {
                $name        = TextNormalizer::normalize($product->name);
                $brand       = TextNormalizer::normalize($product->brand);
                $category    = TextNormalizer::normalize($product->category?->name);
                $synonyms    = TextNormalizer::normalize(
                    $product->category?->synonyms?->pluck('term')->implode(' ') ?? ''
                );
                $description = TextNormalizer::normalize($product->description);

                $score = $term === ''
                    ? 1
                    : $this->scoreMatch($name, $brand, $category, $synonyms, $description, $term, $tokens, $requiresStrongMatch);

                return ['product' => $product, 'score' => $score];
            })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->pipe(fn (Collection $rows) => $this->sortRankedRows($rows, $query))
            ->values()
            ->map(fn (array $row) => $row['product']);
    }

    /**
     * @param list<string> $tokens
     */
    private function scoreMatch(
        string $name,
        string $brand,
        string $category,
        string $synonyms,
        string $description,
        string $term,
        array  $tokens,
        bool   $requiresStrongMatch,
    ): int {
        $strongName     = $this->containsAllTokens($name, $tokens);
        $strongBrand    = $this->containsAllTokens($brand, $tokens);
        $strongCategory = $this->containsAllTokens($category, $tokens);

        if ($requiresStrongMatch && ! ($strongName || $strongBrand || $strongCategory)) {
            return 0;
        }

        $token           = $tokens[0] ?? $term;
        $nameMatch       = str_contains($name, $token)        || str_contains($name, $term);
        $brandMatch      = str_contains($brand, $token)       || str_contains($brand, $term);
        $categoryMatch   = str_contains($category, $token)    || str_contains($category, $term);
        $synonymsMatch   = str_contains($synonyms, $token)    || str_contains($synonyms, $term);
        $descriptionMatch = str_contains($description, $token) || str_contains($description, $term);

        if (! ($nameMatch || $brandMatch || $categoryMatch || $synonymsMatch || $descriptionMatch)) {
            return 0;
        }

        $score  = 0;
        $score += str_contains($name, $term)     ? self::SCORE_NAME_EXACT      : 0;
        $score += $strongName                    ? self::SCORE_NAME_STRONG     : 0;
        $score += str_contains($brand, $term)    ? self::SCORE_BRAND_EXACT     : 0;
        $score += $strongBrand                   ? self::SCORE_BRAND_STRONG    : 0;
        $score += str_contains($category, $term) ? self::SCORE_CATEGORY_EXACT  : 0;
        $score += $strongCategory                ? self::SCORE_CATEGORY_STRONG : 0;
        $score += str_contains($synonyms, $term) ? self::SCORE_SYNONYM_EXACT   : 0;
        $score += $synonymsMatch                 ? self::SCORE_SYNONYM_TOKEN   : 0;
        $score += $descriptionMatch              ? self::SCORE_DESCRIPTION     : 0;

        return $score;
    }

    /**
     * Retorna una expresión SQL case-insensitive y accent-insensitive.
     * En PostgreSQL usa unaccent() (requiere la extensión habilitada).
     * En otros drivers (ej. SQLite en tests) usa lower() como fallback.
     */
    private function ciExpr(string $column): string
    {
        if (DB::getDriverName() === 'pgsql') {
            return "unaccent(lower({$column}))";
        }

        return "lower({$column})";
    }

    /**
     * @param list<string> $tokens
     */
    private function containsAllTokens(string $haystack, array $tokens): bool
    {
        if ($tokens === []) {
            return false;
        }

        foreach ($tokens as $token) {
            if (! str_contains($haystack, $token)) {
                return false;
            }
        }

        return true;
    }

    private function applySort(Builder $builder, ProductSearchQuery $query): Builder
    {
        return match ($query->sort) {
            ProductSearchQuery::SORT_NAME_ASC => $builder
                ->orderBy('products.name')
                ->orderBy('products.id'),
            ProductSearchQuery::SORT_NAME_DESC => $builder
                ->orderByDesc('products.name')
                ->orderByDesc('products.id'),
            ProductSearchQuery::SORT_STOCK_DESC => $builder
                ->orderByRaw('CASE WHEN products.stock IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('products.stock')
                ->orderBy('products.name')
                ->orderBy('products.id'),
            default => $builder
                ->orderByRaw($this->inStockFirstOrderExpression())
                ->orderBy('products.name')
                ->orderBy('products.id'),
        };
    }

    private function sortRankedRows(Collection $rows, ProductSearchQuery $query): Collection
    {
        return match ($query->sort) {
            ProductSearchQuery::SORT_NAME_ASC => $rows->sortBy([
                [fn (array $row) => TextNormalizer::normalize($row['product']->name), 'asc'],
                [fn (array $row) => $row['product']->id, 'asc'],
            ]),
            ProductSearchQuery::SORT_NAME_DESC => $rows->sortBy([
                [fn (array $row) => TextNormalizer::normalize($row['product']->name), 'desc'],
                [fn (array $row) => $row['product']->id, 'desc'],
            ]),
            ProductSearchQuery::SORT_STOCK_DESC => $rows->sortBy([
                [fn (array $row) => $this->stockNullSortValue($row['product']), 'asc'],
                [fn (array $row) => $this->stockSortValue($row['product']), 'desc'],
                [fn (array $row) => TextNormalizer::normalize($row['product']->name), 'asc'],
                [fn (array $row) => $row['product']->id, 'asc'],
            ]),
            default => $rows->sortBy([
                ['score', 'desc'],
                [fn (array $row) => $this->inStockSortValue($row['product']), 'asc'],
                [fn (array $row) => TextNormalizer::normalize($row['product']->name), 'asc'],
            ]),
        };
    }

    private function recordTiming(string $name, float $durationMs, string $description): void
    {
        $request = request();
        if (! $request instanceof \Illuminate\Http\Request) {
            return;
        }

        AddServerTiming::addMetric($request, $name, $durationMs, $description);
    }

    private function inStockSortValue(Product $product): int
    {
        return is_numeric($product->stock) && (float) $product->stock > 0.0 ? 0 : 1;
    }

    private function stockNullSortValue(Product $product): int
    {
        return is_numeric($product->stock) ? 0 : 1;
    }

    private function stockSortValue(Product $product): float
    {
        return is_numeric($product->stock) ? (float) $product->stock : -1.0;
    }

    private function inStockFirstOrderExpression(): string
    {
        return 'CASE WHEN products.stock > 0 THEN 0 ELSE 1 END';
    }

    /**
     * @return array<string, int|string>
     */
    private function safePaginationQuery(ProductSearchQuery $query): array
    {
        $params = [];

        if (filled($query->term)) {
            $params['term'] = $query->term;
        }

        if ($query->categoryId !== null) {
            $params['category_id'] = $query->categoryId;
        }

        if (! $query->includeChildren) {
            $params['include_children'] = 0;
        }

        if ($query->sort !== ProductSearchQuery::SORT_RELEVANCE) {
            $params['sort'] = $query->sort;
        }

        if ($query->perPage !== 20) {
            $params['per_page'] = $query->perPage;
        }

        return $params;
    }
}
