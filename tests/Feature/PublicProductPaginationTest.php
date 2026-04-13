<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductPaginationTest extends TestCase
{
    use RefreshDatabase;

    private int $categorySequence = 1;

    private int $productSequence = 1;

    public function test_catalog_uses_twenty_products_per_page_and_indexes_only_base_page(): void
    {
        $category = $this->createCategory();

        foreach (range(1, 25) as $number) {
            $this->createProduct($category, sprintf('Catalog Product %02d', $number), sprintf('CAT-%02d', $number));
        }

        $firstPage = $this->get(route('catalog.index'));

        $firstPage->assertOk();
        $firstPage->assertSee('Catalog Product 01');
        $firstPage->assertSee('Catalog Product 20');
        $firstPage->assertDontSee('Catalog Product 21');
        $firstPage->assertSee('<meta name="robots" content="index,follow">', false);
        $firstPage->assertSee('<link rel="canonical" href="'.route('catalog.index').'">', false);

        $secondPage = $this->get(route('catalog.index', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('Catalog Product 21');
        $secondPage->assertDontSee('Catalog Product 20');
        $secondPage->assertSee('<meta name="robots" content="noindex,follow">', false);
        $secondPage->assertSee('<link rel="canonical" href="'.route('catalog.index').'">', false);
    }

    public function test_catalog_filtered_results_are_noindex_and_preserve_filter_query(): void
    {
        $targetCategory = $this->createCategory('Target Category');
        $otherCategory = $this->createCategory('Other Category');

        $this->createProduct($targetCategory, 'Filtro Objetivo', 'FILTER-01');
        $this->createProduct($otherCategory, 'Filtro Externo', 'FILTER-02');

        $response = $this->get(route('catalog.index', ['category_id' => $targetCategory->id]));

        $response->assertOk();
        $response->assertSee('Filtro Objetivo');
        $response->assertDontSee('Filtro Externo');
        $response->assertSee('<meta name="robots" content="noindex,follow">', false);
        $response->assertSee(
            '<link rel="canonical" href="'.route('catalog.index', ['category_id' => $targetCategory->id]).'">',
            false,
        );
    }

    public function test_catalog_ajax_payload_supports_load_more(): void
    {
        $category = $this->createCategory();

        foreach (range(1, 25) as $number) {
            $this->createProduct($category, sprintf('Ajax Catalog %02d', $number), sprintf('AJAX-%02d', $number));
        }

        $response = $this->get(
            route('catalog.index', ['page' => 2, 'list' => 'catalog']),
            ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'],
        );

        $response->assertOk();
        $response->assertJson([
            'currentPage' => 2,
            'nextPage' => 3,
            'hasMore' => false,
            'pushUrl' => route('catalog.index', ['page' => 2]),
        ]);
        $response->assertJsonStructure(['html', 'controlsHtml']);
    }

    public function test_product_detail_sections_paginate_independently(): void
    {
        $mainCategory = $this->createCategory('Main Category');
        $otherCategory = $this->createCategory('Other Category');

        $product = $this->createProduct($mainCategory, 'Producto Principal', 'MAIN-01');

        foreach (range(1, 25) as $number) {
            $this->createProduct($mainCategory, sprintf('Related Product %02d', $number), sprintf('REL-%02d', $number));
            $this->createProduct($otherCategory, sprintf('Alternative Product %02d', $number), sprintf('ALT-%02d', $number));
        }

        $baseResponse = $this->get(route('products.show', $product));

        $baseResponse->assertOk();
        $baseResponse->assertSee(
            'href="'.route('catalog.index', ['category_id' => $mainCategory->id]).'"',
            false,
        );
        $baseResponse->assertSee('Related Product 25');
        $baseResponse->assertSee('Related Product 06');
        $baseResponse->assertDontSee('Related Product 05');
        $baseResponse->assertSee('Alternative Product 25');
        $baseResponse->assertSee('Alternative Product 06');
        $baseResponse->assertDontSee('Alternative Product 05');

        $relatedPageTwo = $this->get(route('products.show', [
            'product' => $product,
            'related_page' => 2,
        ]));

        $relatedPageTwo->assertOk();
        $relatedPageTwo->assertSee('Related Product 05');
        $relatedPageTwo->assertSee('Related Product 01');
        $relatedPageTwo->assertDontSee('Related Product 06');
        $relatedPageTwo->assertSee('Alternative Product 25');
        $relatedPageTwo->assertDontSee('Alternative Product 05');
        $relatedPageTwo->assertSee('<link rel="canonical" href="'.route('products.show', $product).'">', false);

        $alternativesPageTwo = $this->get(route('products.show', [
            'product' => $product,
            'alternatives_page' => 2,
        ]));

        $alternativesPageTwo->assertOk();
        $alternativesPageTwo->assertSee('Alternative Product 05');
        $alternativesPageTwo->assertSee('Alternative Product 01');
        $alternativesPageTwo->assertDontSee('Alternative Product 06');
        $alternativesPageTwo->assertSee('Related Product 25');
        $alternativesPageTwo->assertDontSee('Related Product 05');
    }

    public function test_product_detail_ajax_payload_is_scoped_to_requested_list(): void
    {
        $mainCategory = $this->createCategory();
        $otherCategory = $this->createCategory();

        $product = $this->createProduct($mainCategory, 'Main Ajax Product', 'MAIN-AJAX');

        foreach (range(1, 25) as $number) {
            $this->createProduct($mainCategory, sprintf('Related Ajax %02d', $number), sprintf('RAX-%02d', $number));
            $this->createProduct($otherCategory, sprintf('Alternative Ajax %02d', $number), sprintf('AAX-%02d', $number));
        }

        $response = $this->get(
            route('products.show', [
                'product' => $product,
                'related_page' => 2,
                'list' => 'related',
            ]),
            ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'],
        );

        $response->assertOk();
        $response->assertJson([
            'currentPage' => 2,
            'nextPage' => 3,
            'hasMore' => false,
            'pushUrl' => route('products.show', ['product' => $product, 'related_page' => 2]),
        ]);
        $response->assertJsonStructure(['html', 'controlsHtml']);
    }

    public function test_product_detail_related_cards_fall_back_to_first_photo_when_primary_missing(): void
    {
        $category = $this->createCategory('Photo Category');

        $product = $this->createProduct($category, 'Main Product', 'MAIN-PHOTO');
        $relatedProduct = $this->createProduct($category, 'Related Product', 'REL-PHOTO');

        $fallbackPhoto = $relatedProduct->photos()->create([
            'path' => 'products/photos/related-fallback.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $relatedProduct->photos()->create([
            'path' => 'products/photos/related-second.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Related Product');
        $response->assertSee($fallbackPhoto->path);
    }

    private function createCategory(?string $name = null): Category
    {
        $sequence = $this->categorySequence++;

        return Category::create([
            'name' => $name ?? "Category {$sequence}",
            'slug' => "category-{$sequence}",
            'is_active' => true,
            'sort_order' => $sequence,
        ]);
    }

    private function createProduct(Category $category, string $name, string $sku): Product
    {
        $sequence = $this->productSequence++;

        return Product::create([
            'name' => $name,
            'brand' => "Brand {$sequence}",
            'sku' => $sku,
            'description' => "Description {$sequence}",
            'category_id' => $category->id,
            'price' => 1000 + $sequence,
            'stock' => 50,
            'is_active' => true,
        ]);
    }
}
