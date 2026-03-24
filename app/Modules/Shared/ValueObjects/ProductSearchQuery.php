<?php

namespace App\Modules\Shared\ValueObjects;

use App\Modules\Shared\Support\TextNormalizer;

class ProductSearchQuery
{
    public function __construct(
        public readonly ?string $term           = null,
        public readonly ?int    $categoryId     = null,
        public readonly bool    $includeChildren = true,
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
}
