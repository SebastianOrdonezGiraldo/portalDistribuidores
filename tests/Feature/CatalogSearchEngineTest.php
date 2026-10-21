<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Shared\Contracts\SearchEngineInterface;
use App\Modules\Shared\ValueObjects\ProductSearchQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchEngineTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Insumos',
            'slug' => 'insumos',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_term_search_sorts_by_name_ascending_and_descending(): void
    {
        foreach (['Guante D', 'Guante B', 'Guante E', 'Guante A', 'Guante C'] as $name) {
            $this->product($name);
        }

        $this->assertSame(
            ['Guante A', 'Guante B', 'Guante C', 'Guante D', 'Guante E'],
            $this->searchNames(['term' => 'guante', 'sort' => ProductSearchQuery::SORT_NAME_ASC]),
        );
        $this->assertSame(
            ['Guante E', 'Guante D', 'Guante C', 'Guante B', 'Guante A'],
            $this->searchNames(['term' => 'guante', 'sort' => ProductSearchQuery::SORT_NAME_DESC]),
        );
    }

    public function test_term_search_sorts_by_available_stock_with_unknown_stock_last(): void
    {
        $this->product('Guante Sin Dato', ['stock' => null]);
        $this->product('Guante Agotado', ['stock' => 0]);
        $this->product('Guante Mucho', ['stock' => 50]);
        $this->product('Guante Poco', ['stock' => 3]);

        $this->assertSame(
            ['Guante Mucho', 'Guante Poco', 'Guante Agotado', 'Guante Sin Dato'],
            $this->searchNames(['term' => 'guante', 'sort' => ProductSearchQuery::SORT_STOCK_DESC]),
        );
    }

    public function test_relevance_ties_put_available_products_first_then_sort_by_name(): void
    {
        $this->product('Guante C', ['stock' => 0]);
        $this->product('Guante B', ['stock' => 4]);
        $this->product('Guante A', ['stock' => 0]);
        $this->product('Guante D', ['stock' => 2]);

        $this->assertSame(
            ['Guante B', 'Guante D', 'Guante A', 'Guante C'],
            $this->searchNames(['term' => 'guante']),
        );
    }

    public function test_relevance_ranks_name_matches_above_description_matches(): void
    {
        $this->product('Caja Basica', ['description' => 'Incluye jeringa']);
        $this->product('Jeringa 5ml');

        $this->assertSame(['Jeringa 5ml', 'Caja Basica'], $this->searchNames(['term' => 'jeringa']));
    }

    public function test_multi_word_terms_match_name_brand_or_category_containing_every_word(): void
    {
        $gloves = Category::create([
            'name' => 'Guantes Nitrilo',
            'slug' => 'guantes-nitrilo',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->product('Guante Talla M');
        $this->product('Caja Talla Grande', ['description' => 'guante talla m incluido']);
        $this->product('Protector', ['brand' => 'Guante Talla M Pro']);
        $this->product('Producto Generico', ['category_id' => $gloves->id]);

        $this->assertSame(
            ['Guante Talla M', 'Protector'],
            $this->searchNames(['term' => 'guante talla m']),
        );
        $this->assertSame(['Producto Generico'], $this->searchNames(['term' => 'guantes nitrilo']));
    }

    public function test_single_word_terms_match_category_synonyms_and_description(): void
    {
        $this->category->synonyms()->create(['term' => 'material medico']);
        $this->product('Gasa Esteril');
        $this->product('Venda Elastica', ['description' => 'Uso hospitalario']);

        $this->assertSame(['Gasa Esteril', 'Venda Elastica'], $this->searchNames(['term' => 'medico']));
        $this->assertSame(['Venda Elastica'], $this->searchNames(['term' => 'hospitalario']));
    }

    public function test_like_wildcards_in_the_term_are_matched_literally(): void
    {
        $this->product('Repuesto REF_2');
        $this->product('Repuesto REFX2');
        $this->product('Alcohol 10% antiseptico');
        $this->product('Alcohol 100 ml');

        $this->assertSame(['Repuesto REF_2'], $this->searchNames(['term' => 'ref_2']));
        $this->assertSame(['Alcohol 10% antiseptico'], $this->searchNames(['term' => '10%']));
        $this->assertSame(['Alcohol 10% antiseptico'], $this->searchNames(['term' => '%']));
    }

    public function test_pagination_returns_each_ranked_product_once(): void
    {
        foreach (range(1, 7) as $index) {
            $this->product("Guante {$index}", ['stock' => $index % 2]);
        }

        $names = [];

        foreach ([1, 2, 3] as $page) {
            $result = app(SearchEngineInterface::class)->search(
                ProductSearchQuery::fromArray(['term' => 'guante', 'per_page' => 3, 'page' => $page]),
            );

            $this->assertSame(7, $result->total());
            $names = [...$names, ...$result->getCollection()->pluck('name')->all()];
        }

        $this->assertCount(7, array_unique($names));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(string $name, array $attributes = []): Product
    {
        static $sequence = 0;
        $sequence++;

        return Product::create([
            'name' => $name,
            'sku' => "SEARCH-{$sequence}",
            'category_id' => $this->category->id,
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<string>
     */
    private function searchNames(array $params): array
    {
        return app(SearchEngineInterface::class)
            ->search(ProductSearchQuery::fromArray($params))
            ->getCollection()
            ->pluck('name')
            ->all();
    }
}
