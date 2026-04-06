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

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guest_can_download_active_tech_sheet(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['document' => $document] = $this->createTechSheetDocument();

        $this->get(route('documents.tech-sheet.download', $document))
            ->assertOk()
            ->assertDownload('a.pdf');
    }

    public function test_guest_can_download_active_manual_document(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['document' => $document] = $this->createProtectedDocument('manual', 'manual.pdf');

        $this->get(route('documents.manual.download', $document))
            ->assertOk()
            ->assertDownload('manual.pdf');
    }

    public function test_distributor_can_download_a_tech_sheet_two_times_per_month(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-15 10:00:00', 'UTC'));

        $user = $this->createDistributorUser();
        ['document' => $document] = $this->createTechSheetDocument();

        $firstResponse = $this->actingAs($user)
            ->get(route('documents.tech-sheet.download', $document));

        $secondResponse = $this->actingAs($user)
            ->get(route('documents.tech-sheet.download', $document));

        $firstResponse->assertOk()->assertDownload('a.pdf');
        $secondResponse->assertOk()->assertDownload('a.pdf');
        $this->assertDatabaseCount('document_downloads', 2);
    }

    public function test_distributor_can_download_manual_without_consuming_quota(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $user = $this->createDistributorUser();
        ['document' => $document] = $this->createProtectedDocument('manual', 'manual.pdf');

        $this->actingAs($user)
            ->get(route('documents.manual.download', $document))
            ->assertOk()
            ->assertDownload('manual.pdf');

        $this->assertDatabaseCount('document_downloads', 0);
    }

    public function test_distributor_cannot_download_more_than_two_times_per_month(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 08:00:00', 'UTC'));

        $distributor = Distributor::create(['name' => 'Dist', 'status' => 'active']);
        $user = $this->createDistributorUser($distributor);
        ['product' => $product, 'document' => $document] = $this->createTechSheetDocument();

        for ($i = 0; $i < 2; $i++) {
            DocumentDownload::create([
                'distributor_id' => $distributor->id,
                'product_document_id' => $document->id,
                'downloaded_at' => CarbonImmutable::now()->subDays($i)->setTimezone('UTC'),
            ]);
        }

        $response = $this->actingAs($user)
            ->from(route('products.show', $product))
            ->get(route('documents.tech-sheet.download', $document));

        $response->assertRedirect(route('products.show', $product));
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('document_downloads', 2);
    }

    public function test_distributor_download_limit_resets_when_a_new_month_starts_in_bogota(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        config([
            'documents.tech_sheet_monthly_limit' => 2,
            'documents.tech_sheet_monthly_timezone' => 'America/Bogota',
        ]);

        $distributor = Distributor::create(['name' => 'Dist', 'status' => 'active']);
        $user = $this->createDistributorUser($distributor);
        ['document' => $document] = $this->createTechSheetDocument();

        DocumentDownload::create([
            'distributor_id' => $distributor->id,
            'product_document_id' => $document->id,
            'downloaded_at' => CarbonImmutable::parse('2026-04-01 03:30:00', 'UTC'),
        ]);

        DocumentDownload::create([
            'distributor_id' => $distributor->id,
            'product_document_id' => $document->id,
            'downloaded_at' => CarbonImmutable::parse('2026-04-01 04:30:00', 'UTC'),
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01 05:15:00', 'UTC'));

        $this->actingAs($user)
            ->get(route('documents.tech-sheet.download', $document))
            ->assertOk()
            ->assertDownload('a.pdf');

        $this->assertDatabaseCount('document_downloads', 3);
    }

    public function test_admin_can_download_without_consuming_quota(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        ['document' => $document] = $this->createTechSheetDocument();

        $this->actingAs($admin)
            ->get(route('documents.tech-sheet.download', $document))
            ->assertOk()
            ->assertDownload('a.pdf');

        $this->assertDatabaseCount('document_downloads', 0);
    }

    public function test_product_page_shows_the_configured_monthly_limit(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        config(['documents.tech_sheet_monthly_limit' => 2]);

        $user = $this->createDistributorUser();
        ['product' => $product] = $this->createTechSheetDocument();

        $this->actingAs($user)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSeeText('2/2 descargas restantes este mes');
    }

    public function test_product_page_lists_manual_download_link(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['product' => $product, 'document' => $document] = $this->createProtectedDocument('manual', 'manual.pdf');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Manual de usuario')
            ->assertSeeText('Descargar manual')
            ->assertSee(route('documents.manual.download', $document), false);
    }

    /**
     * @return array{product: Product, document: ProductDocument}
     */
    private function createTechSheetDocument(): array
    {
        return $this->createProtectedDocument('tech_sheet', 'a.pdf');
    }

    /**
     * @return array{product: Product, document: ProductDocument}
     */
    private function createProtectedDocument(string $type, string $filename): array
    {
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

        $path = 'products/documents/'.$filename;
        Storage::disk('private')->put($path, 'PDF');
        $document = ProductDocument::create([
            'product_id' => $product->id,
            'type' => $type,
            'path' => $path,
            'filename' => $filename,
        ]);

        return compact('product', 'document');
    }

    private function createDistributorUser(?Distributor $distributor = null): User
    {
        $distributor ??= Distributor::create(['name' => 'Dist', 'status' => 'active']);

        return User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);
    }
}
