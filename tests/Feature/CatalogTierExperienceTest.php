<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Pricing\DistributorTierMetricsService;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTierExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_uses_distributor_section_label(): void
    {
        $user = $this->distributorUser(DistributorTier::Silver);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('>Distribuidor</p>', false);
        $response->assertDontSee('>Comercial</p>', false);
    }

    public function test_gold_catalog_shows_active_discount_experience(): void
    {
        $user = $this->distributorUser(DistributorTier::Gold);
        $this->seedCatalogProduct();

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Tu estatus Oro te da descuento en TODOS los productos');
        $response->assertSee('Ahorro acumulado');
        $response->assertSee('Descuento Oro activo');
        $response->assertSee('Beneficios de tu nivel');
        $response->assertSee('Mostrando precios Oro');
        $response->assertSee('Nivel Oro');
        $response->assertSee('Precio Oro');
        $response->assertDontSee('promociones activas', false);
    }

    public function test_silver_catalog_shows_locked_discount_and_upgrade_modal(): void
    {
        $user = $this->distributorUser(DistributorTier::Silver);
        $this->seedCatalogProduct();

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Ahorro potencial este mes');
        $response->assertSee('Descuento Oro');
        $response->assertSee('Ver precios Oro');
        $response->assertSee('Sube a Nivel Oro');
        $response->assertSee('tier-upgrade', false);
        $response->assertSee('Ahorrarías', false);
        $response->assertSee('Nivel Plata');
        $response->assertSee('images/tiers/banner-plata.jpg', false);
    }

    public function test_metrics_service_sums_snapshot_delta_only(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $product = Product::factory()->create(['price' => 100000]);

        $withSnapshot = Order::factory()->create([
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Sold,
            'created_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $withSnapshot->id,
            'product_id' => $product->id,
            'qty' => 2,
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 106000,
            'unit_savings' => 6000,
            'subtotal' => 200000,
            'line_savings' => 12000,
        ]);

        $withoutSnapshot = Order::factory()->create([
            'distributor_id' => $distributor->id,
            'status' => OrderStatus::Sold,
            'created_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $withoutSnapshot->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price_each' => 100000,
            'base_unit_price' => null,
            'silver_unit_price' => null,
            'unit_savings' => null,
            'subtotal' => 100000,
            'line_savings' => null,
        ]);

        $metrics = app(DistributorTierMetricsService::class)->forDistributor($distributor);

        $this->assertSame(1_200_000, $metrics->savingsCents);
        $this->assertSame(2, $metrics->ordersCount);
        $this->assertSame(DistributorTier::Gold->benefitsCount(), $metrics->benefitsCount());
    }

    private function distributorUser(DistributorTier $tier): User
    {
        $distributor = Distributor::factory()->create([
            'tier' => $tier,
            'status' => 'active',
        ]);

        return User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
            'name' => 'Distribuidor Staging',
        ]);
    }

    private function seedCatalogProduct(): Product
    {
        $category = Category::factory()->create(['name' => 'Accesorios']);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Adaptador Electro Estimuladores',
            'sku' => 'ADP-001',
            'brand' => 'Compass Health',
            'price' => 96000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }
}
