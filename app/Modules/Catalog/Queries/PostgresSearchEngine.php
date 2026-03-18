<?php

namespace App\Modules\Catalog\Queries;

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
        $normalizedTerm = $query->normalizedTerm();

        if ($normalizedTerm === '') {
            return $this
                ->baseQuery($query)
                ->with(['category', 'primaryPhoto', 'variantAttribute', 'variants.attributeValue'])
                ->orderBy('products.name')
                ->orderBy('products.id')
                ->paginate($query->perPage, ['products.*'], 'page', $query->page)
                ->withQueryString();
        }

        // Pre-filtrar en SQL: solo traer productos que contengan al menos
        // un token en nombre, marca, descripción, categoría o sinónimos.
        // El scoring detallado (pesos, strong-match, etc.) se mantiene en PHP
        // pero solo se ejecuta sobre este subconjunto reducido de candidatos.
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
            ->with(['category.synonyms', 'primaryPhoto', 'variantAttribute', 'variants.attributeValue'])
            ->get();

        $ranked = $this->rank($candidates, $query);

        $total  = $ranked->count();
        $offset = ($query->page - 1) * $query->perPage;
        $items  = $ranked->slice($offset, $query->perPage)->values();

        $paginationPath  = $query->paginationUrl  !== '' ? $query->paginationUrl  : request()->url();
        $paginationQuery = $query->paginationQuery !== '' ? $query->paginationQuery : request()->query();

        return new Paginator(
            $items,
            $total,
            $query->perPage,
            $query->page,
            ['path' => $paginationPath, 'query' => $paginationQuery],
        );
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
            ->sortBy([
                ['score', 'desc'],
                [fn (array $row) => $row['product']->name, 'asc'],
            ])
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
}
