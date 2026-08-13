<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\DTOs\CreateOrderData;
use App\Modules\Orders\Events\OrderPlaced;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Pricing\DistributorTierResolver;
use App\Modules\Orders\Pricing\OrderPricingCalculator;
use App\Modules\Orders\Pricing\OrderPricingSnapshotMapper;
use App\Modules\Orders\Services\CommerceTierAdvisorProvider;
use App\Modules\Orders\Services\OrderAdvisorResolver;
use App\Modules\Orders\Services\OrderInventoryService;
use App\Modules\Orders\Services\OrderStatusTransitionService;
use App\Modules\Orders\Services\Payment\OrderPaymentService;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\GoldThresholdBasis;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_submitted_order_decreases_inventory_records_initial_status_and_dispatches_event(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())
            ->method('recordInitialStatus')
            ->with(
                $this->callback(function (Order $order) use ($user): bool {
                    return $order->exists
                        && $order->user_id === $user->id
                        && $order->distributor_id === $user->distributor_id
                        && $order->status === OrderStatus::Submitted
                        && str_starts_with($order->oc_number, Order::OC_PREFIX);
                }),
                $user,
                'Estado inicial registrado al crear la cotización.'
            );

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())
            ->method('holdForOrder')
            ->with(
                $this->callback(fn (Order $order) => $order->status === OrderStatus::Submitted),
                $this->anything(),
                'checkout_submitted',
            );

        $action = $this->makeAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'cajas'],
        ]));

        $this->assertSame(OrderStatus::Submitted, $order->status);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($user->distributor_id, $order->distributor_id);
        $this->assertSame('CTC-000001', $order->oc_number);
        $this->assertSame('20000.00', $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'qty' => 2,
            'unit_label' => 'cajas',
            'price_each' => 10000,
            'subtotal' => 20000,
            'is_vat_excluded_snapshot' => false,
        ]);

        $this->assertSame(0.13, (float) $order->items()->firstOrFail()->vat_rate_snapshot);

        Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event) => $event->order->is($order));
    }

    public function test_execute_persists_vat_excluded_snapshot_without_changing_final_total(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 34000,
            'stock' => 10,
            'is_active' => true,
            'is_vat_excluded' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $action = $this->makeAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $item = $order->items()->firstOrFail();

        $this->assertSame('68000.00', $order->total_amount);
        $this->assertTrue((bool) $item->is_vat_excluded_snapshot);
        $this->assertSame(0.0, (float) $item->vat_rate_snapshot);
        $this->assertSame('34000.00', $item->price_each);
        $this->assertSame('68000.00', $item->subtotal);
    }

    public function test_execute_creates_pending_approval_order_without_inventory_decrease_or_event(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 5000,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())
            ->method('recordInitialStatus')
            ->with(
                $this->callback(fn (Order $order) => $order->status === OrderStatus::PendingApproval),
                $user,
                'Estado inicial registrado al crear la cotización.'
            );

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->never())->method('holdForOrder');

        $action = $this->makeAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 1, 'unit_label' => 'unidades'],
        ], requiresApproval: true));

        $this->assertSame(OrderStatus::PendingApproval, $order->status);
        Event::assertNotDispatched(OrderPlaced::class);
    }

    public function test_execute_uses_variant_price_and_persists_variant_snapshots(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 10000,
            'is_active' => true,
        ]);
        $attribute = ProductAttribute::factory()->create(['name' => 'Talla']);
        $attributeValue = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'value' => 'M',
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'product_attribute_value_id' => $attributeValue->id,
            'price' => 15000,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $action = $this->makeAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => $variant->id, 'qty' => 3, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame('45000.00', $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_attribute_snapshot' => 'Talla',
            'variant_value_snapshot' => 'M',
            'price_each' => 15000,
            'subtotal' => 45000,
        ]);
    }

    public function test_execute_skips_invalid_items_and_normalizes_quantity_before_persisting(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $simpleProduct = Product::factory()->create([
            'price' => 8000,
            'is_active' => true,
        ]);
        $configurableProduct = Product::factory()->create([
            'price' => 12000,
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $configurableProduct->id,
            'is_active' => true,
        ]);
        $otherProduct = Product::factory()->create([
            'price' => 9000,
            'is_active' => true,
        ]);
        $foreignVariant = ProductVariant::factory()->create([
            'product_id' => $otherProduct->id,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $action = $this->makeAction($statusService, $inventoryService);

        $order = $action->execute($user, $this->makeOrderData([
            ['product_id' => $simpleProduct->id, 'variant_id' => null, 'qty' => 0, 'unit_label' => 'packs'],
            ['product_id' => $configurableProduct->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'unidades'],
            ['product_id' => $simpleProduct->id, 'variant_id' => $foreignVariant->id, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame('8000.00', $order->total_amount);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $simpleProduct->id,
            'qty' => 1,
            'unit_label' => 'packs',
            'subtotal' => 8000,
        ]);
    }

    public function test_execute_throws_when_cart_cannot_produce_any_valid_line(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $configurableProduct = Product::factory()->create([
            'price' => 12000,
            'is_active' => true,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $configurableProduct->id,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->never())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->never())->method('holdForOrder');

        $action = $this->makeAction($statusService, $inventoryService);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('El carrito no puede generar una orden vacía.');

        $action->execute($user, $this->makeOrderData([
            ['product_id' => $configurableProduct->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));
    }

    public function test_execute_rolls_back_order_and_skips_follow_up_side_effects_when_inventory_fails(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create([
            'price' => 7000,
            'is_active' => true,
        ]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->never())->method('recordInitialStatus');

        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())
            ->method('holdForOrder')
            ->willThrowException(new DomainException('stock error'));

        $action = $this->makeAction($statusService, $inventoryService);

        try {
            $action->execute($user, $this->makeOrderData([
                ['product_id' => $product->id, 'variant_id' => null, 'qty' => 1, 'unit_label' => 'unidades'],
            ]));
            $this->fail('Expected DomainException was not thrown.');
        } catch (DomainException $exception) {
            $this->assertSame('stock error', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        Event::assertNotDispatched(OrderPlaced::class);
    }

    public function test_gold_order_stores_base_price_as_effective_and_gold_snapshot(): void
    {
        Event::fake([OrderPlaced::class]);

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');
        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $order = $this->makeAction($statusService, $inventoryService)->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 1, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame(DistributorTier::Gold, $order->distributor_tier_snapshot);
        $this->assertTrue($order->gold_pricing_applied);
        $this->assertSame('minimum_not_required', $order->minimum_order_decision_reason_snapshot);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 5000,
            'subtotal' => 100000,
            'line_savings' => 5000,
        ]);
    }

    public function test_create_order_below_gold_threshold_pays_silver_and_allows_checkout(): void
    {
        Event::fake([OrderPlaced::class]);

        $admin = User::factory()->admin()->create();
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            silverMinOrderEnabled: true,
            silverMinOrderAmount: 800_000,
            goldMinOrderEnabled: false,
            goldPricingThresholdEnabled: true,
            goldPricingThresholdAmount: 1_000_000,
        );

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create(['price' => 96_000, 'stock' => 20, 'is_active' => true]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');
        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $order = $this->makeAction($statusService, $inventoryService)->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 7, 'unit_label' => 'unidades'],
        ]));

        $this->assertFalse($order->gold_pricing_applied);
        $this->assertSame('threshold_not_reached', $order->gold_pricing_decision_reason_snapshot);
        $this->assertSame('minimum_not_required', $order->minimum_order_decision_reason_snapshot);
        $this->assertSame('707000.00', $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 101000,
            'base_unit_price' => 96000,
            'silver_unit_price' => 101000,
            'unit_savings' => 0,
            'subtotal' => 707000,
            'line_savings' => 0,
        ]);
    }

    public function test_create_order_persists_pricing_snapshots_from_same_result(): void
    {
        Event::fake([OrderPlaced::class]);

        $admin = User::factory()->admin()->create();
        $publish = app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            goldMinOrderEnabled: false,
            goldPricingThresholdEnabled: true,
            goldPricingThresholdAmount: 1_000_000,
            goldPricingThresholdBasis: GoldThresholdBasis::GoldCandidate,
        );

        $user = $this->makeDistributorUser();
        $product = Product::factory()->create(['price' => 1_050_000, 'stock' => 10, 'is_active' => true]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');
        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $order = $this->makeAction($statusService, $inventoryService)->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 1, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame($publish->rule->id, $order->commerce_pricing_rule_id);
        $this->assertSame(DistributorTier::Gold, $order->distributor_tier_snapshot);
        $this->assertFalse($order->minimum_order_enabled_snapshot);
        $this->assertSame('minimum_not_required', $order->minimum_order_decision_reason_snapshot);
        $this->assertTrue($order->gold_pricing_threshold_enabled_snapshot);
        $this->assertSame(1_000_000, $order->gold_pricing_threshold_amount_snapshot);
        $this->assertSame('gold_candidate', $order->gold_pricing_threshold_basis_snapshot);
        $this->assertSame('threshold_reached', $order->gold_pricing_decision_reason_snapshot);
        $this->assertTrue($order->gold_pricing_applied);
        $this->assertSame('1050000.00', $order->gold_candidate_total);
        $this->assertSame('1050000.00', $order->total_amount);
        $this->assertGreaterThan(0, (float) $order->gold_savings_total);
    }

    public function test_silver_order_stores_silver_price_as_effective_and_silver_snapshot(): void
    {
        Event::fake([OrderPlaced::class]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');
        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $order = $this->makeAction($statusService, $inventoryService)->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 2, 'unit_label' => 'unidades'],
        ]));

        $this->assertSame(DistributorTier::Silver, $order->distributor_tier_snapshot);
        $this->assertSame('210000.00', $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 0,
            'subtotal' => 210000,
            'line_savings' => 0,
        ]);
    }

    public function test_changing_tier_or_price_after_order_does_not_change_the_order(): void
    {
        Event::fake([OrderPlaced::class]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $statusService = $this->createMock(OrderStatusTransitionService::class);
        $statusService->expects($this->once())->method('recordInitialStatus');
        $inventoryService = $this->createMock(OrderInventoryService::class);
        $inventoryService->expects($this->once())->method('holdForOrder');

        $order = $this->makeAction($statusService, $inventoryService)->execute($user, $this->makeOrderData([
            ['product_id' => $product->id, 'variant_id' => null, 'qty' => 1, 'unit_label' => 'unidades'],
        ]));

        $item = $order->items()->firstOrFail();

        // Mutate the world after the order is confirmed.
        $distributor->update(['tier' => DistributorTier::Silver]);
        $product->update(['price' => 500000]);

        $item->refresh();
        $order->refresh();

        $this->assertSame(DistributorTier::Gold, $order->distributor_tier_snapshot);
        $this->assertSame('100000.00', $item->price_each);
        $this->assertSame('100000.00', $item->base_unit_price);
        $this->assertSame('105000.00', $item->silver_unit_price);
        $this->assertSame('100000.00', $order->total_amount);
    }

    private function makeAction(
        OrderStatusTransitionService $statusService,
        OrderInventoryService $inventoryService,
    ): CreateOrderAction {
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

    /**
     * Gold distributor so effective price equals the stored base price, keeping
     * the historical price assertions in these tests valid.
     */
    private function makeDistributorUser(): User
    {
        $distributor = Distributor::factory()->gold()->create();

        return User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);
    }

    /**
     * @param  array<int, array{product_id:int,variant_id:int|null,qty:int,unit_label:string}>  $items
     */
    private function makeOrderData(array $items, bool $requiresApproval = false): CreateOrderData
    {
        return new CreateOrderData(
            contactName: 'Comprador Test',
            contactEmail: 'comprador@test.com',
            phone: '3001234567',
            companyName: 'Empresa Test',
            companyNit: '9001234567',
            companyAddress: 'Calle 123',
            city: 'Bogota',
            department: 'Cundinamarca',
            notes: 'nota de prueba',
            items: $items,
            requiresApproval: $requiresApproval,
        );
    }
}
