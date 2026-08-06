<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductShowTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_product_show_omits_specifications_tab_and_panel(): void
    {
        $category = Category::create([
            'name' => 'Estética Facial',
            'slug' => 'estetica-facial',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto sin especificaciones tab',
            'brand' => 'Marca Demo',
            'sku' => 'SKU-NO-SPECS',
            'description' => 'Descripción comercial del producto.',
            'category_id' => $category->id,
            'price' => 150000,
            'stock' => 12,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertDontSee('Especificaciones técnicas');
        $response->assertDontSee('product-tab-especificaciones', false);
        $response->assertDontSee('product-panel-especificaciones', false);
        $response->assertDontSee('data-tab-panel="especificaciones"', false);
        $response->assertDontSee('>Especificaciones<', false);

        $response->assertSee('Descripción');
        $response->assertSee('Documentos');
        $response->assertSee('Alternativas');

        $response->assertSee('SKU-NO-SPECS');
        $response->assertSee('Marca Demo');
        $response->assertSee('Estética Facial');
    }

    public function test_public_product_empty_description_does_not_mention_technical_specs(): void
    {
        $category = Category::create([
            'name' => 'Categoría Vacía',
            'slug' => 'categoria-vacia',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto sin descripción',
            'brand' => 'Marca Vacía',
            'sku' => 'SKU-EMPTY-DESC',
            'description' => null,
            'category_id' => $category->id,
            'price' => 99000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Sin descripción comercial');
        $response->assertSee('Consulta los documentos adjuntos para validar la compra o contacta a tu asesor comercial.');
        $response->assertDontSee('especificaciones técnicas');
        $response->assertDontSee('Especificaciones técnicas');
        $response->assertDontSee('product-panel-especificaciones', false);
    }
}
