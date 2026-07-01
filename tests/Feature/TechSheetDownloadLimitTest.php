<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Categories\Models\Category;
use App\Modules\Documents\Models\DocumentDownload;
use App\Modules\Shared\Enums\DocumentType;
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

    public function test_guest_can_download_active_invima_document(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['document' => $document] = $this->createProtectedDocument(DocumentType::Invima->value, 'invima.pdf');

        $this->get(route('documents.invima.download', $document))
            ->assertOk()
            ->assertDownload('invima.pdf');
    }

    public function test_guest_can_download_active_quick_guide_document(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['document' => $document] = $this->createProtectedDocument(DocumentType::QuickGuide->value, 'guia-rapida.pdf');

        $this->get(route('documents.quick-guide.download', $document))
            ->assertOk()
            ->assertDownload('guia-rapida.pdf');
    }

    public function test_guest_can_download_active_calibration_document(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['document' => $document] = $this->createProtectedDocument(DocumentType::CalibrationDocument->value, 'calibracion.pdf');

        $this->get(route('documents.calibration-document.download', $document))
            ->assertOk()
            ->assertDownload('calibracion.pdf');
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

    public function test_distributor_can_download_invima_quick_guide_and_calibration_without_consuming_quota(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $user = $this->createDistributorUser();
        ['product' => $product, 'document' => $invima] = $this->createProtectedDocument(DocumentType::Invima->value, 'invima.pdf');
        $quickGuide = $this->attachProtectedDocument($product, DocumentType::QuickGuide->value, 'guia-rapida.pdf');
        $calibrationDocument = $this->attachProtectedDocument($product, DocumentType::CalibrationDocument->value, 'calibracion.pdf');

        $this->actingAs($user)
            ->get(route('documents.invima.download', $invima))
            ->assertOk()
            ->assertDownload('invima.pdf');

        $this->actingAs($user)
            ->get(route('documents.quick-guide.download', $quickGuide))
            ->assertOk()
            ->assertDownload('guia-rapida.pdf');

        $this->actingAs($user)
            ->get(route('documents.calibration-document.download', $calibrationDocument))
            ->assertOk()
            ->assertDownload('calibracion.pdf');

        $this->assertDatabaseCount('document_downloads', 0);
    }

    public function test_document_download_route_rejects_mismatched_document_type(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['document' => $document] = $this->createProtectedDocument(DocumentType::Invima->value, 'invima.pdf');

        $this->get(route('documents.manual.download', $document))
            ->assertNotFound();
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

    public function test_product_page_lists_invima_quick_guide_and_calibration_download_links(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        ['product' => $product, 'document' => $invima] = $this->createProtectedDocument(DocumentType::Invima->value, 'invima.pdf');
        $quickGuide = $this->attachProtectedDocument($product, DocumentType::QuickGuide->value, 'guia-rapida.pdf');
        $calibrationDocument = $this->attachProtectedDocument($product, DocumentType::CalibrationDocument->value, 'calibracion.pdf');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSeeText('Descargar INVIMA')
            ->assertSee(route('documents.invima.download', $invima), false)
            ->assertSeeText('Descargar guía rápida')
            ->assertSee(route('documents.quick-guide.download', $quickGuide), false)
            ->assertSeeText('Documento de calibracion')
            ->assertSee(route('documents.calibration-document.download', $calibrationDocument), false);
    }

    /**
     * @return array{product: Product, document: ProductDocument}
     */
    private function createTechSheetDocument(): array
    {
        return $this->createProtectedDocument(DocumentType::TechSheet->value, 'a.pdf');
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

        $document = $this->attachProtectedDocument($product, $type, $filename);

        return compact('product', 'document');
    }

    private function attachProtectedDocument(Product $product, string $type, string $filename): ProductDocument
    {
        $path = 'products/documents/'.$filename;
        Storage::disk('private')->put($path, 'PDF');

        return ProductDocument::create([
            'product_id' => $product->id,
            'type' => $type,
            'path' => $path,
            'filename' => $filename,
        ]);
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
