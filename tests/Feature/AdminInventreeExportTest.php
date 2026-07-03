<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
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

        Product::create([
            'name' => 'Resistencia 10K',
            'brand' => 'ACME',
            'sku' => 'SKU-10K',
            'description' => str_repeat('X', 300),
            'category_id' => $category->id,
            'price' => 1500,
            'stock' => 25,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/inventory/export');

        $response->assertOk();
        $response->assertDownload('inventree-productos.csv');
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('IPN,name,description,pricing_min,total_in_stock,active,keywords', $csv);
        $this->assertStringContainsString('SKU-10K,"Resistencia 10K",', $csv);
        $this->assertStringContainsString(str_repeat('X', 250), $csv);
    }
}
