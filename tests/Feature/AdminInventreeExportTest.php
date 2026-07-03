<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventreeExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_inventree_csv_export(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Componentes',
            'slug' => 'componentes',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $attribute = ProductAttribute::factory()->create([
            'name' => 'Color',
            'slug' => 'color',
        ]);

        $variantValue = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'value' => 'Azul',
            'slug' => 'azul',
        ]);

        $product = Product::create([
            'name' => 'Resistencia 10K',
            'sku' => 'SKU-10K',
            'category_id' => $category->id,
            'price' => 1500,
            'stock' => 25,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'product_attribute_value_id' => $variantValue->id,
            'price' => 1750,
            'stock' => 7,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/inventory/export');

        $response->assertOk();
        $response->assertDownload('inventree-productos.csv');
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('IPN,name,pricing_min,total_in_stock,active', $csv);
        $this->assertStringContainsString('SKU-10K,"Resistencia 10K",1500.00,25.00,true', $csv);
        $this->assertStringContainsString('SKU-10K-azul,"Resistencia 10K - Azul",1750.00,7.00,true', $csv);
    }
}
