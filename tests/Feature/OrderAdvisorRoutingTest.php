<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\CommerceTierAdvisor;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Pricing\DistributorTierResolver;
use App\Modules\Orders\Pricing\OrderPricingCalculator;
use App\Modules\Orders\Pricing\OrderPricingSnapshotMapper;
use App\Modules\Orders\Services\CommerceTierAdvisorProvider;
use App\Modules\Orders\Services\OrderAdvisorResolver;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Orders\Services\Payment\OrderPaymentService;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderAdvisorRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pricing_fallback_does_not_create_advisor_snapshots(): void
    {
        Event::fake([OrderPlaced::class]);
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10, 'is_active' => true]);
        $action = $this->makeAction();

        $order = $action->execute(null, $this->orderData($product));

        $this->assertSame(DistributorTier::Silver, $order->distributor_tier_snapshot);
        $this->assertNull($order->distributor_id);
        $this->assertNull($order->advisor_name_snapshot);
        $this->assertNull($order->advisor_email_snapshot);
        $this->assertNull($order->advisor_whatsapp_snapshot);
    }

    public function test_real_distributor_tier_creates_channel_snapshots(): void
    {
        Event::fake([OrderPlaced::class]);
        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        CommerceTierAdvisor::query()->create([
            'tier' => DistributorTier::Gold,
            'advisor_name' => 'Carolina Oro',
            'advisor_email' => 'carolina@example.test',
            'advisor_whatsapp' => '573117479607',
        ]);
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10, 'is_active' => true]);

        $order = $this->makeAction()->execute($user, $this->orderData($product));

        $this->assertSame('Carolina Oro', $order->advisor_name_snapshot);
        $this->assertSame('carolina@example.test', $order->advisor_email_snapshot);
        $this->assertSame('573117479607', $order->advisor_whatsapp_snapshot);

        CommerceTierAdvisor::query()->updateOrCreate(
            ['tier' => DistributorTier::Gold],
            [
                'advisor_name' => 'Nueva Asesora',
                'advisor_email' => 'nueva@example.test',
                'advisor_whatsapp' => '573001112233',
            ],
        );

        $this->assertSame('Carolina Oro', $order->fresh()->advisor_name_snapshot);
        $this->assertSame('carolina@example.test', $order->fresh()->advisor_email_snapshot);
    }

    public function test_advisor_channels_are_validated_independently(): void
    {
        CommerceTierAdvisor::query()->create([
            'tier' => DistributorTier::Gold,
            'advisor_name' => 'Nombre válido',
            'advisor_email' => 'correo inválido',
            'advisor_whatsapp' => '573001112233',
        ]);

        $advisor = app(CommerceTierAdvisorProvider::class)->forTier(DistributorTier::Gold);

        $this->assertNotNull($advisor);
        $this->assertSame('Nombre válido', $advisor->validName());
        $this->assertNull($advisor->validEmail());
        $this->assertSame('573001112233', $advisor->validWhatsapp());
        $this->assertStringStartsWith('https://wa.me/573001112233', (string) $advisor->whatsappUrl('Hola'));
    }

    private function makeAction(): CreateOrderAction
    {
        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');
        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        return new CreateOrderAction(
            $statusService,
            $inventoryService,
            new DistributorTierResolver,
            app(OrderPricingCalculator::class),
            app(OrderPaymentService::class),
            app(OrderPricingSnapshotMapper::class),
            app(CommerceTierAdvisorProvider::class),
            app(OrderAdvisorResolver::class),
        );
    }

    private function orderData(Product $product): CreateOrderData
    {
        return new CreateOrderData(
            contactName: 'Comprador Test',
            contactEmail: 'comprador@example.test',
            phone: '3001234567',
            companyName: 'Empresa Test',
            companyNit: '9001234567',
            companyAddress: 'Calle 123',
            city: 'Bogota',
            department: 'Cundinamarca',
            notes: null,
            items: [[
                'product_id' => $product->id,
                'variant_id' => null,
                'qty' => 1,
                'unit_label' => 'unidad',
            ]],
        );
    }
}
