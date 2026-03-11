<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Queries\CategoryDescendantsQuery;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use App\Modules\Shared\Support\TextNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class PostgresSearchEngine implements SearchEngineInterface
{
    public function __construct(
        private readonly CategoryDescendantsQuery $categoryDescendantsQuery,
    ) {
    }

    public function search(ProductSearchQuery $query): LengthAwarePaginator
    {
        $normalizedTerm = $query->normalizedTerm();

        if ($normalizedTerm === '') {
            return $this
                ->baseQuery($query, withSynonyms: false)
                ->orderBy('name')
                ->orderBy('id')
                ->paginate($query->perPage, ['*'], 'page', $query->page)
                ->withQueryString();
        }

        $products = $this->baseQuery($query, withSynonyms: true)->get();

        $ranked = $this->rank($products, $query);

        $total = $ranked->count();
        $offset = ($query->page - 1) * $query->perPage;
        $items = $ranked->slice($offset, $query->perPage)->values();

        return new Paginator(
            $items,
            $total,
            $query->perPage,
            $query->page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    private function baseQuery(ProductSearchQuery $query, bool $withSynonyms): Builder
    {
        $with = $withSynonyms ? ['category.synonyms', 'primaryPhoto'] : ['category', 'primaryPhoto'];
        $with[] = 'variantAttribute';
        $with[] = 'variants.attributeValue';

        return Product::query()
            ->with($with)
            ->where('is_active', true)
            ->when($query->categoryId !== null, function (Builder $builder) use ($query) {
                if ($query->includeChildren) {
                    $categoryIds = $this->categoryDescendantsQuery->execute($query->categoryId);
                    $builder->whereIn('category_id', $categoryIds);

                    return;
                }

                $builder->where('category_id', $query->categoryId);
            });
    }

    private function rank(Collection $products, ProductSearchQuery $query): Collection
    {
        $term = $query->normalizedTerm();
        $tokens = $query->tokens();
        $requiresStrongMatch = count($tokens) >= 2;

        return $products
            ->map(function (Product $product) use ($term, $tokens, $requiresStrongMatch) {
                $name = TextNormalizer::normalize($product->name);
                $brand = TextNormalizer::normalize($product->brand);
                $category = TextNormalizer::normalize($product->category?->name);
                $synonyms = TextNormalizer::normalize(
                    $product->category?->synonyms?->pluck('term')->implode(' ') ?? ''
                );
                $description = TextNormalizer::normalize($product->description);

                if ($term === '') {
                    $score = 1;
                } else {
                    $score = $this->scoreMatch($name, $brand, $category, $synonyms, $description, $term, $tokens, $requiresStrongMatch);
                }

                return [
                    'product' => $product,
                    'score' => $score,
                ];
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
        array $tokens,
        bool $requiresStrongMatch,
    ): int {
        $strongName = $this->containsAllTokens($name, $tokens);
        $strongBrand = $this->containsAllTokens($brand, $tokens);
        $strongCategory = $this->containsAllTokens($category, $tokens);

        if ($requiresStrongMatch && ! ($strongName || $strongBrand || $strongCategory)) {
            return 0;
        }

        $token = $tokens[0] ?? $term;
        $nameMatch = str_contains($name, $token) || str_contains($name, $term);
        $brandMatch = str_contains($brand, $token) || str_contains($brand, $term);
        $categoryMatch = str_contains($category, $token) || str_contains($category, $term);
        $synonymsMatch = str_contains($synonyms, $token) || str_contains($synonyms, $term);
        $descriptionMatch = str_contains($description, $token) || str_contains($description, $term);

        if (! ($nameMatch || $brandMatch || $categoryMatch || $synonymsMatch || $descriptionMatch)) {
            return 0;
        }

        $score = 0;
        $score += str_contains($name, $term) ? 500 : 0;
        $score += $strongName ? 380 : 0;
        $score += str_contains($brand, $term) ? 340 : 0;
        $score += $strongBrand ? 280 : 0;
        $score += str_contains($category, $term) ? 260 : 0;
        $score += $strongCategory ? 200 : 0;
        $score += str_contains($synonyms, $term) ? 120 : 0;
        $score += $synonymsMatch ? 80 : 0;
        $score += $descriptionMatch ? 20 : 0;

        return $score;
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
