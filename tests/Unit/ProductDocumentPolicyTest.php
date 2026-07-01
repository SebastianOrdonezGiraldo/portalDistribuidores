<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Documents\Policies\ProductDocumentPolicy;
use App\Modules\Shared\Enums\DocumentType;
use Tests\TestCase;

class ProductDocumentPolicyTest extends TestCase
{
    private ProductDocumentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ProductDocumentPolicy;
    }

    public function test_download_rejects_unprotected_documents(): void
    {
        $document = $this->makeDocumentWithProduct('brochure', true);

        $this->assertFalse($this->policy->download(null, $document));
        $this->assertFalse($this->policy->download(User::factory()->admin()->make(), $document));
    }

    public function test_download_rejects_protected_documents_for_inactive_or_missing_product(): void
    {
        $inactiveProductDocument = $this->makeDocumentWithProduct(DocumentType::TechSheet->value, false);
        $missingProductDocument = new ProductDocument([
            'type' => DocumentType::Manual->value,
            'path' => 'docs/manual.pdf',
            'filename' => 'manual.pdf',
        ]);

        $this->assertFalse($this->policy->download(null, $inactiveProductDocument));
        $this->assertFalse($this->policy->download(null, $missingProductDocument));
    }

    public function test_download_allows_guest_for_protected_documents_of_active_product(): void
    {
        $document = $this->makeDocumentWithProduct(DocumentType::TechSheet->value, true);

        $this->assertTrue($this->policy->download(null, $document));
    }

    public function test_download_allows_guest_for_regulatory_protected_documents_of_active_product(): void
    {
        foreach ([DocumentType::Invima, DocumentType::QuickGuide, DocumentType::CalibrationDocument] as $documentType) {
            $document = $this->makeDocumentWithProduct($documentType->value, true);

            $this->assertTrue($this->policy->download(null, $document));
        }
    }

    public function test_download_allows_admin_for_protected_documents_of_active_product(): void
    {
        $admin = User::factory()->admin()->make();
        $document = $this->makeDocumentWithProduct(DocumentType::Manual->value, true);

        $this->assertTrue($this->policy->download($admin, $document));
    }

    public function test_download_allows_only_distributor_with_distributor_id_among_non_admin_users(): void
    {
        $document = $this->makeDocumentWithProduct(DocumentType::TechSheet->value, true);
        $distributorWithCompany = User::factory()->make(['distributor_id' => 123]);
        $distributorWithoutCompany = User::factory()->make(['distributor_id' => null]);
        $inactiveDistributor = User::factory()->inactive()->make(['distributor_id' => 123]);
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->download($distributorWithCompany, $document));
        $this->assertFalse($this->policy->download($distributorWithoutCompany, $document));
        $this->assertTrue($this->policy->download($inactiveDistributor, $document));
        $this->assertTrue($this->policy->download($admin, $document));
    }

    private function makeDocumentWithProduct(string $type, bool $productIsActive): ProductDocument
    {
        $product = new Product([
            'name' => 'Producto protegido',
            'brand' => 'Marca',
            'sku' => 'SKU-PROTECTED-001',
            'description' => 'Descripcion',
            'category_id' => 1,
            'price' => 10000,
            'stock' => 10,
            'is_active' => $productIsActive,
        ]);
        $document = new ProductDocument([
            'type' => $type,
            'path' => 'docs/file.pdf',
            'filename' => 'file.pdf',
        ]);
        $document->setRelation('product', $product);

        return $document;
    }
}
