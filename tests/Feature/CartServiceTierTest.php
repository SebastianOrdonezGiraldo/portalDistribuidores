<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\AuthAccess\Services\DistributorTierService;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\GoldThresholdBasis;
use App\Modules\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_uses_silver_price(): void
    {
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $cart = app(CartService::class);
        $cart->add($product, 1);

        $line = $cart->items()->firstOrFail();

        $this->assertSame(DistributorTier::Silver, $line['tier']);
        $this->assertSame(100000.0, $line['base_unit_price']);
        $this->assertSame(105000.0, $line['silver_unit_price']);
        $this->assertSame(105000.0, $line['unit_price']);
        $this->assertSame(105000.0, $line['subtotal']);
    }

    public function test_gold_distributor_cart_uses_base_price(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        $cart = app(CartService::class);
        $cart->add($product, 2);

        $line = $cart->items()->firstOrFail();

        $this->assertSame(DistributorTier::Gold, $line['tier']);
        $this->assertSame(100000.0, $line['unit_price']);
        $this->assertSame(105000.0, $line['silver_unit_price']);
        $this->assertSame(5000.0, $line['unit_savings']);
        $this->assertSame(200000.0, $line['subtotal']);
        $this->assertSame(10000.0, $line['line_savings']);
    }

    public function test_silver_distributor_cart_uses_silver_price(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        $cart = app(CartService::class);
        $cart->add($product, 1);

        $this->assertSame(105000.0, $cart->items()->firstOrFail()['unit_price']);
    }

    public function test_variant_uses_its_own_price(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);

        $product = Product::factory()->create(['price' => 100000, 'is_active' => true]);
        $attribute = ProductAttribute::factory()->create(['name' => 'Talla']);
        $value = ProductAttributeValue::factory()->create(['product_attribute_id' => $attribute->id, 'value' => 'M']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'product_attribute_value_id' => $value->id,
            'price' => 200000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $cart = app(CartService::class);
        $cart->add($product, 1, 'unidad', $variant);

        $line = $cart->items()->firstOrFail();

        $this->assertSame(200000.0, $line['base_unit_price']);
        $this->assertSame(200000.0, $line['unit_price']);
    }

    public function test_tier_change_recalculates_open_cart(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->assertSame(105000.0, app(CartService::class)->items()->firstOrFail()['unit_price']);

        app(DistributorTierService::class)->changeTier($distributor, DistributorTier::Gold, User::factory()->admin()->create());

        // Simulate a fresh request so the distributor relation is reloaded.
        $this->actingAs($user->fresh());

        $this->assertSame(100000.0, app(CartService::class)->items()->firstOrFail()['unit_price']);
    }

    public function test_stock_is_still_validated(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 2, 'is_active' => true]);

        $this->actingAs($user);

        $this->expectException(DomainException::class);

        app(CartService::class)->add($product, 5);
    }

    public function test_gold_cart_always_uses_gold_price_below_minimum(): void
    {
        $admin = User::factory()->admin()->create();
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            goldMinOrderEnabled: true,
            goldMinOrderAmount: 1_000_000,
            goldPricingThresholdEnabled: true,
            goldPricingThresholdAmount: 1_000_000,
            goldPricingThresholdBasis: GoldThresholdBasis::GoldCandidate,
        );

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 700_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        $cart = app(CartService::class);
        $cart->add($product, 1);

        $line = $cart->items()->firstOrFail();
        $pricing = $cart->pricingResult();

        $this->assertSame(700000.0, $line['unit_price']);
        $this->assertSame(35000.0, $line['line_savings']);
        $this->assertTrue($pricing?->goldPricingApplied);
        $this->assertFalse($pricing?->checkoutAllowed());
    }

    public function test_gold_cart_blocks_checkout_when_minimum_not_met(): void
    {
        $admin = User::factory()->admin()->create();
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            goldMinOrderEnabled: true,
            goldMinOrderAmount: 800_000,
            goldPricingThresholdEnabled: true,
            goldPricingThresholdAmount: 1_000_000,
        );

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 700_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        $cart = app(CartService::class);
        $cart->add($product, 1);

        $pricing = $cart->pricingResult();
        $this->assertFalse($pricing?->checkoutAllowed());
        $this->assertTrue($pricing?->goldPricingApplied);
        $this->assertSame(70_000_000, $pricing?->effectiveTotalCents);
    }
}
