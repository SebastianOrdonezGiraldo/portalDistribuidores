<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Categories\Models\Category;
use App\Modules\Documents\Models\DocumentDownload;
use App\Modules\Shared\Enums\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechSheetDownloadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_download_active_tech_sheet(): void
    {
        Storage::fake('public');

        $category = Category::create([
            'name' => 'Proteccion',
            'slug' => 'proteccion',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto A',
            'sku' => 'TS-001',
            'description' => 'desc',
            'category_id' => $category->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('products/documents/a.pdf', 'PDF');
        $document = ProductDocument::create([
            'product_id' => $product->id,
            'type' => 'tech_sheet',
            'path' => 'products/documents/a.pdf',
            'filename' => 'a.pdf',
        ]);

        $this->get(route('documents.tech-sheet.download', $document))
            ->assertOk()
            ->assertDownload('a.pdf');
    }

    public function test_distributor_cannot_download_more_than_three_times_per_month(): void
    {
        Storage::fake('public');

        $distributor = Distributor::create(['name' => 'Dist', 'status' => 'active']);
        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Proteccion',
            'slug' => 'proteccion',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'name' => 'Producto A',
            'sku' => 'TS-001',
            'description' => 'desc',
            'category_id' => $category->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('products/documents/a.pdf', 'PDF');
        $document = ProductDocument::create([
            'product_id' => $product->id,
            'type' => 'tech_sheet',
            'path' => 'products/documents/a.pdf',
            'filename' => 'a.pdf',
        ]);

        for ($i = 0; $i < 3; $i++) {
            DocumentDownload::create([
                'distributor_id' => $distributor->id,
                'product_document_id' => $document->id,
                'downloaded_at' => CarbonImmutable::now()->subDays($i),
            ]);
        }

        $response = $this->actingAs($user)
            ->from(route('products.show', $product))
            ->get(route('documents.tech-sheet.download', $document));

        $response->assertRedirect(route('products.show', $product));
        $response->assertSessionHasErrors();
    }
}
