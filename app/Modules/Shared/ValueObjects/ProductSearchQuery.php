<?php

namespace App\Modules\Shared\ValueObjects;

use App\Modules\Shared\Support\TextNormalizer;

class ProductSearchQuery
{
    public const SORT_RELEVANCE = 'relevance';
    public const SORT_NAME_ASC = 'name_asc';
    public const SORT_NAME_DESC = 'name_desc';
    public const SORT_STOCK_DESC = 'stock_desc';

    public const AVAILABLE_SORTS = [
        self::SORT_RELEVANCE,
        self::SORT_NAME_ASC,
        self::SORT_NAME_DESC,
        self::SORT_STOCK_DESC,
    ];

    public function __construct(
        public readonly ?string $term           = null,
        public readonly ?int    $categoryId     = null,
        public readonly bool    $includeChildren = true,
        public readonly string  $sort           = self::SORT_RELEVANCE,
        public readonly int     $page           = 1,
        public readonly int     $perPage        = 20,
        public readonly string  $paginationUrl  = '',
        public readonly string  $paginationQuery = '',
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            term:             $payload['term'] ?? null,
            categoryId:       isset($payload['category_id']) ? (int) $payload['category_id'] : null,
            includeChildren:  (bool) ($payload['include_children'] ?? true),
            sort:             self::normalizeSort($payload['sort'] ?? self::SORT_RELEVANCE),
            page:             max(1, (int) ($payload['page'] ?? 1)),
            perPage:          max(1, min(50, (int) ($payload['per_page'] ?? 20))),
            paginationUrl:    (string) ($payload['pagination_url'] ?? ''),
            paginationQuery:  (string) ($payload['pagination_query'] ?? ''),
        );
    }

    public function normalizedTerm(): string
    {
        return TextNormalizer::normalize($this->term);
    }

    /**
     * @return list<string>
     */
    public function tokens(): array
    {
        return TextNormalizer::tokenize($this->term);
    }

    private static function normalizeSort(mixed $sort): string
    {
        $value = is_string($sort) ? $sort : self::SORT_RELEVANCE;

        return in_array($value, self::AVAILABLE_SORTS, true)
            ? $value
            : self::SORT_RELEVANCE;
    }
}
