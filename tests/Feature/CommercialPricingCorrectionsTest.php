<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Actions\UpdateOrderAction;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Pricing\CommercePricingRulesProvider;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Pricing\OrderPricingCalculator;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\GoldThresholdBasis;
use App\Modules\Shared\Enums\OrderStatus;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommercialPricingCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_not_subject_to_silver_minimum(): void
    {
        $admin = User::factory()->admin()->create();
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            silverMinOrderEnabled: true,
            silverMinOrderAmount: 5_000_000,
        );

        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);
        $cart = app(CartService::class);
        $cart->add($product, 1);

        $pricing = $cart->pricingResult();
        $this->assertNotNull($pricing);
        $this->assertTrue($pricing->checkoutAllowed());
        $this->assertSame('tier_not_subject_to_minimum', $pricing->minimumOrderDecision->reason);
        $this->assertSame(105_000.0, $cart->items()->firstOrFail()['unit_price']);
    }

    public function test_admin_is_not_subject_to_silver_minimum(): void
    {
        $admin = User::factory()->admin()->create();
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            silverMinOrderEnabled: true,
            silverMinOrderAmount: 5_000_000,
        );

        $this->actingAs($admin);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);
        $cart = app(CartService::class);
        $cart->add($product, 1);

        $pricing = $cart->pricingResult();
        $this->assertTrue($pricing?->checkoutAllowed());
        $this->assertSame('tier_not_subject_to_minimum', $pricing?->minimumOrderDecision->reason);
    }

    public function test_provider_falls_back_to_config_without_hardcoded_amounts(): void
    {
        DB::table('commerce_pricing_rules')->delete();
        config([
            'commerce.tiers.silver_min_order_enabled' => true,
            'commerce.tiers.silver_min_order_amount' => 777_000,
            'commerce.tiers.gold_min_order_enabled' => false,
            'commerce.tiers.gold_min_order_amount' => 888_000,
            'commerce.tiers.gold_pricing_threshold_enabled' => true,
            'commerce.tiers.gold_pricing_threshold_amount' => 999_000,
            'commerce.tiers.gold_pricing_threshold_basis' => 'silver_candidate',
        ]);

        $rules = app(CommercePricingRulesProvider::class)->current();

        $this->assertTrue($rules->silverMinOrderEnabled);
        $this->assertSame(777_000, $rules->silverMinOrderAmount);
        $this->assertSame(888_000, $rules->goldMinOrderAmount);
        $this->assertTrue($rules->goldPricingThresholdEnabled);
        $this->assertSame(999_000, $rules->goldPricingThresholdAmount);
        $this->assertSame(GoldThresholdBasis::SilverCandidate, $rules->goldPricingThresholdBasis);
    }

    public function test_update_order_recalculates_all_lines_and_keeps_original_rule(): void
    {
        $admin = User::factory()->admin()->create();
        $original = app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            goldPricingThresholdEnabled: true,
            goldPricingThresholdAmount: 1_000_000,
        )->rule;

        $distributor = Distributor::factory()->gold()->create();
        $order = Order::factory()->forDistributor($distributor)->pendingApproval()->create([
            'distributor_tier_snapshot' => DistributorTier::Gold,
            'commerce_pricing_rule_id' => $original->id,
            'total_amount' => 0,
        ]);

        $productA = Product::factory()->create(['price' => 100_000, 'is_active' => true, 'stock' => 20]);
        $productB = Product::factory()->create(['price' => 200_000, 'is_active' => true, 'stock' => 20]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'product_name_snapshot' => $productA->name,
            'sku_snapshot' => $productA->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 100000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 5000,
            'subtotal' => 100000,
            'line_savings' => 5000,
        ]);

        // Publish a different current rule that would otherwise change outcomes.
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 700,
            silverRoundingMultiple: 1000,
            actor: $admin,
            goldPricingThresholdEnabled: false,
        );

        $updated = app(UpdateOrderAction::class)->execute($order, [
            'contact_name' => 'Contacto',
            'contact_email' => 'a@test.com',
            'phone' => '300',
            'company_name' => 'Co',
            'company_nit' => '1',
            'company_address' => 'x',
            'city' => 'Bogota',
            'department' => 'Cundinamarca',
            'notes' => null,
            'items' => [['id' => $item->id, 'qty' => 2, 'unit_label' => 'unidades']],
            'new_items' => [['catalog_ref' => 'p:'.$productB->id, 'qty' => 1, 'unit_label' => 'unidades']],
        ]);

        $this->assertSame($original->id, $updated->commerce_pricing_rule_id);
        $this->assertTrue((bool) $updated->gold_pricing_applied);
        $this->assertSame('always_applied', $updated->gold_pricing_decision_reason_snapshot);

        $lines = $updated->items()->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertTrue($lines->every(fn (OrderItem $line) => (float) $line->price_each === (float) $line->base_unit_price));
        $this->assertTrue($lines->every(fn (OrderItem $line) => (float) $line->unit_savings > 0.0));
        // Original markup 5%, not the later 7%.
        $this->assertSame('105000.00', $lines->firstWhere('product_id', $productA->id)?->silver_unit_price);
        $this->assertSame('210000.00', $lines->firstWhere('product_id', $productB->id)?->silver_unit_price);
    }

    public function test_historical_order_without_rule_fk_adopts_current_on_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $current = app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
        )->rule;

        $distributor = Distributor::factory()->silver()->create();
        $order = Order::factory()->forDistributor($distributor)->pendingApproval()->create([
            'distributor_tier_snapshot' => DistributorTier::Silver,
            'commerce_pricing_rule_id' => null,
            'total_amount' => 0,
        ]);
        $product = Product::factory()->create(['price' => 100_000, 'is_active' => true, 'stock' => 10]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 0,
            'subtotal' => 105000,
            'line_savings' => 0,
        ]);

        $updated = app(UpdateOrderAction::class)->execute($order, [
            'contact_name' => 'Contacto',
            'contact_email' => 'a@test.com',
            'phone' => '300',
            'company_name' => 'Co',
            'company_nit' => '1',
            'company_address' => 'x',
            'city' => 'Bogota',
            'department' => 'Cundinamarca',
            'notes' => null,
            'items' => [['id' => $item->id, 'qty' => 2, 'unit_label' => 'unidades']],
            'new_items' => [],
        ]);

        $this->assertSame($current->id, $updated->commerce_pricing_rule_id);
        $this->assertNotNull($updated->minimum_order_decision_reason_snapshot);
        $this->assertSame('210000.00', $updated->total_amount);
    }

    public function test_update_rejects_when_pending_order_below_minimum(): void
    {
        $admin = User::factory()->admin()->create();
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            silverMinOrderEnabled: true,
            silverMinOrderAmount: 500_000,
        );

        $distributor = Distributor::factory()->silver()->create();
        $order = Order::factory()->forDistributor($distributor)->pendingApproval()->create([
            'distributor_tier_snapshot' => DistributorTier::Silver,
            'commerce_pricing_rule_id' => CommercePricingRule::query()->orderByDesc('id')->value('id'),
            'total_amount' => 0,
        ]);
        $product = Product::factory()->create(['price' => 100_000, 'is_active' => true, 'stock' => 10]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'qty' => 1,
            'unit_label' => 'unidades',
            'price_each' => 105000,
            'base_unit_price' => 100000,
            'silver_unit_price' => 105000,
            'unit_savings' => 0,
            'subtotal' => 105000,
            'line_savings' => 0,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Pedido mínimo');

        app(UpdateOrderAction::class)->execute($order, [
            'contact_name' => 'Contacto',
            'contact_email' => 'a@test.com',
            'phone' => '300',
            'company_name' => 'Co',
            'company_nit' => '1',
            'company_address' => 'x',
            'city' => 'Bogota',
            'department' => 'Cundinamarca',
            'notes' => null,
            'items' => [['id' => $item->id, 'qty' => 1, 'unit_label' => 'unidades']],
            'new_items' => [],
        ]);
    }

    public function test_referenced_commerce_rule_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $rule = app(CommercePricingRulesService::class)->publish(500, 1000, $admin)->rule;
        $distributor = Distributor::factory()->silver()->create();
        Order::factory()->forDistributor($distributor)->create([
            'commerce_pricing_rule_id' => $rule->id,
            'distributor_tier_snapshot' => DistributorTier::Silver,
            'status' => OrderStatus::Submitted,
            'total_amount' => 1000,
        ]);

        $this->expectException(QueryException::class);
        CommercePricingRule::query()->whereKey($rule->id)->delete();
    }

    public function test_calculator_null_tier_reason_is_tier_not_subject(): void
    {
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 5, 'is_active' => true]);
        $result = app(OrderPricingCalculator::class)->calculate(null, [
            ['product' => $product, 'qty' => 1],
        ]);

        $this->assertTrue($result->checkoutAllowed());
        $this->assertSame('tier_not_subject_to_minimum', $result->minimumOrderDecision->reason);
        $this->assertFalse($result->goldPricingApplied);
    }
}
