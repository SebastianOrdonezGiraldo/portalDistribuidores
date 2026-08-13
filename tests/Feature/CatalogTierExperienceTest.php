<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Orders\Models\CommerceTierAdvisor;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Pricing\DistributorPriceCalculator;
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
        $response->assertSee('Tu Nivel Oro ya está activo en todo el catálogo');
        $response->assertSee('Ahorro acumulado');
        $response->assertSee('Tu precio Oro');
        $response->assertSee('Precio 2025');
        $response->assertSee('Beneficios de tu nivel');
        $response->assertSee('Como eres Cliente Oro');
        $response->assertSee('Ver mis beneficios');
        $response->assertSee('Estefanía López');
        $response->assertSee('Nivel Oro');
        $response->assertSee('Precio Oro');
        $response->assertSee('tier-upgrade', false);
        $response->assertDontSee('Ver precios Oro');
        $response->assertDontSee('Mostrando precios Oro');
        $response->assertDontSee('promociones activas', false);
    }

    public function test_silver_catalog_shows_locked_discount_and_upgrade_modal(): void
    {
        $user = $this->distributorUser(DistributorTier::Silver);
        $this->seedCatalogProduct();

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('Ahorro potencial este mes');
        $response->assertSee('Precio 2025');
        $response->assertSee('Sube a Nivel Oro');
        $response->assertSee('tier-upgrade', false);
        $response->assertSee('Ahorrarías', false);
        $response->assertSee('Nivel Plata');
        $response->assertSee('images/tiers/banner-plata.jpg', false);
        $response->assertDontSee('Ver precios Oro');
        $response->assertDontSee('Estefanía López');
    }

    public function test_gold_product_detail_shows_dual_pricing(): void
    {
        $user = $this->distributorUser(DistributorTier::Gold);
        $product = $this->seedCatalogProduct();

        $response = $this->actingAs($user)->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Precio Oro');
        $response->assertSee('Ahorras', false);
        $response->assertSee('product-detail-price__hero--gold', false);
        $response->assertDontSee('Mostrando precios Oro');
        $response->assertDontSee('Ver precios Oro');
    }

    public function test_silver_product_detail_shows_locked_dual_pricing(): void
    {
        $user = $this->distributorUser(DistributorTier::Silver);
        $product = $this->seedCatalogProduct();

        $response = $this->actingAs($user)->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Tu precio');
        $response->assertSee('Con Oro');
        $response->assertSee('Ahorrarías', false);
        $response->assertSee('product-detail-price__nudge', false);
        $response->assertDontSee('product-detail-price__hero--gold', false);
    }

    public function test_guest_product_detail_keeps_single_price(): void
    {
        $product = $this->seedCatalogProduct();
        [$formattedGold, $formattedSilver] = $this->formattedTierPrices($product);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertDontSee('product-detail-price__nudge', false);
        $response->assertDontSee('product-detail-price__hero--gold', false);
        // Guests see list/Plata (2026), never the Gold/base discount price.
        $response->assertSee($formattedSilver);
        $response->assertDontSee($formattedGold);
    }

    public function test_guest_catalog_shows_silver_list_price(): void
    {
        $product = $this->seedCatalogProduct();
        [$formattedGold, $formattedSilver] = $this->formattedTierPrices($product);

        $response = $this->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee($formattedSilver);
        $response->assertDontSee($formattedGold);
        $response->assertDontSee('Precio Oro');
        $response->assertDontSee('Ahorras', false);
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
        $this->assertSame(DistributorTier::Gold->benefitsCount(), $metrics->benefitsCount);
    }

    private function distributorUser(DistributorTier $tier): User
    {
        $distributor = Distributor::factory()->create([
            'tier' => $tier,
            'status' => 'active',
        ]);

        CommerceTierAdvisor::query()->create([
            'tier' => $tier,
            'advisor_name' => $tier === DistributorTier::Gold ? 'Estefanía López' : 'Asesor Plata',
            'advisor_email' => $tier === DistributorTier::Gold ? 'oro@example.test' : 'plata@example.test',
            'advisor_whatsapp' => $tier === DistributorTier::Gold ? '573117479607' : '573001112233',
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

    /**
     * @return array{0: string, 1: string}
     */
    private function formattedTierPrices(Product $product): array
    {
        $tierPrice = app(DistributorPriceCalculator::class)
            ->calculateFromDecimal((string) $product->price, DistributorTier::Silver);

        $formatMoney = static fn (string $amount): string => '$'.number_format((float) $amount, 0, ',', '.');

        return [
            $formatMoney($tierPrice->basePriceDecimal()),
            $formatMoney($tierPrice->silverPriceDecimal()),
        ];
    }
}
