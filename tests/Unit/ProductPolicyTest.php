<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Policies\ProductPolicy;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    private ProductPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ProductPolicy;
    }

    public function test_view_any_allows_admin_and_distributor(): void
    {
        $admin = User::factory()->admin()->make();
        $distributor = User::factory()->make();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($distributor));
    }

    public function test_view_allows_admin_regardless_of_product_status(): void
    {
        $admin = User::factory()->admin()->make();
        $inactiveProduct = $this->makeProduct(false);

        $this->assertTrue($this->policy->view($admin, $inactiveProduct));
    }

    public function test_view_allows_distributor_only_for_active_products(): void
    {
        $distributor = User::factory()->make();
        $activeProduct = $this->makeProduct(true);
        $inactiveProduct = $this->makeProduct(false);

        $this->assertTrue($this->policy->view($distributor, $activeProduct));
        $this->assertFalse($this->policy->view($distributor, $inactiveProduct));
    }

    public function test_create_update_and_delete_are_admin_only(): void
    {
        $admin = User::factory()->admin()->make();
        $distributor = User::factory()->make();
        $product = $this->makeProduct(true);

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($distributor));

        $this->assertTrue($this->policy->update($admin, $product));
        $this->assertFalse($this->policy->update($distributor, $product));

        $this->assertTrue($this->policy->delete($admin, $product));
        $this->assertFalse($this->policy->delete($distributor, $product));
    }

    private function makeProduct(bool $isActive): Product
    {
        return new Product([
            'name' => 'Producto Test',
            'brand' => 'Marca Test',
            'sku' => 'SKU-TEST-001',
            'description' => 'Descripcion',
            'category_id' => 1,
            'price' => 10000,
            'stock' => 5,
            'is_active' => $isActive,
        ]);
    }
}
