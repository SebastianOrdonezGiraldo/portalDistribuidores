<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Queries\CategoryDescendantsQuery;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use App\Modules\Shared\Support\TextNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        $products = Product::query()
            ->with(['category.synonyms', 'primaryPhoto'])
            ->where('is_active', true)
            ->when($query->categoryId !== null, function ($builder) use ($query) {
                if ($query->includeChildren) {
                    $categoryIds = $this->categoryDescendantsQuery->execute($query->categoryId);
                    $builder->whereIn('category_id', $categoryIds);

                    return;
                }

                $builder->where('category_id', $query->categoryId);
            })
            ->get();

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

    private function rank(Collection $products, ProductSearchQuery $query): Collection
    {
        $term = $query->normalizedTerm();
        $tokens = $query->tokens();
        $requiresStrongMatch = count($tokens) >= 2;

        return $products
            ->map(function (Product $product) use ($term, $tokens, $requiresStrongMatch) {
                $name = TextNormalizer::normalize($product->name);
                $category = TextNormalizer::normalize($product->category?->name);
                $synonyms = TextNormalizer::normalize(
                    $product->category?->synonyms?->pluck('term')->implode(' ') ?? ''
                );
                $description = TextNormalizer::normalize($product->description);

                if ($term === '') {
                    $score = 1;
                } else {
                    $score = $this->scoreMatch($name, $category, $synonyms, $description, $term, $tokens, $requiresStrongMatch);
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
        string $category,
        string $synonyms,
        string $description,
        string $term,
        array $tokens,
        bool $requiresStrongMatch,
    ): int {
        $strongName = $this->containsAllTokens($name, $tokens);
        $strongCategory = $this->containsAllTokens($category, $tokens);

        if ($requiresStrongMatch && ! ($strongName || $strongCategory)) {
            return 0;
        }

        $token = $tokens[0] ?? $term;
        $nameMatch = str_contains($name, $token) || str_contains($name, $term);
        $categoryMatch = str_contains($category, $token) || str_contains($category, $term);
        $synonymsMatch = str_contains($synonyms, $token) || str_contains($synonyms, $term);
        $descriptionMatch = str_contains($description, $token) || str_contains($description, $term);

        if (! ($nameMatch || $categoryMatch || $synonymsMatch || $descriptionMatch)) {
            return 0;
        }

        $score = 0;
        $score += str_contains($name, $term) ? 500 : 0;
        $score += $strongName ? 380 : 0;
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

