<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Enums\GoldThresholdBasis;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceRulesUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publishRules(User $admin, array $overrides = []): void
    {
        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            silverMinOrderEnabled: $overrides['silverMinOrderEnabled'] ?? false,
            silverMinOrderAmount: $overrides['silverMinOrderAmount'] ?? 1_000_000,
            goldMinOrderEnabled: $overrides['goldMinOrderEnabled'] ?? false,
            goldMinOrderAmount: $overrides['goldMinOrderAmount'] ?? 1_000_000,
            goldPricingThresholdEnabled: $overrides['goldPricingThresholdEnabled'] ?? false,
            goldPricingThresholdAmount: $overrides['goldPricingThresholdAmount'] ?? 1_000_000,
            goldPricingThresholdBasis: $overrides['goldPricingThresholdBasis'] ?? GoldThresholdBasis::GoldCandidate,
        );
    }

    public function test_admin_commerce_page_renders_minimum_and_gold_sections(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.commerce.edit'))
            ->assertOk()
            ->assertSee('Pedidos mínimos')
            ->assertSee('Define el valor mínimo que debe alcanzar un pedido para poder finalizar la compra.')
            ->assertSee('Exigir pedido mínimo para Cliente Plata')
            ->assertSee('Exigir pedido mínimo para Cliente Oro')
            ->assertSee('Activación de precios Oro')
            ->assertSee('Exigir monto mínimo para activar precios Oro')
            ->assertSee('Total calculado con precios Oro')
            ->assertSee('Total calculado con precios Plata')
            ->assertSee('Guardar reglas comerciales');
    }

    public function test_admin_commerce_page_shows_disabled_state_copy_and_history_rules(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 500_000,
            'goldMinOrderEnabled' => false,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.commerce.edit'))
            ->assertOk()
            ->assertSee('Los clientes de este nivel pueden comprar sin pedido mínimo')
            ->assertSee('Pedido mínimo Plata')
            ->assertSee('Activo — $500.000', false)
            ->assertSee('Inactivo')
            ->assertSee('Candidato Oro');
    }

    public function test_admin_validation_errors_appear_next_to_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.commerce.edit'))
            ->patch(route('admin.settings.commerce.update'), [
                'silver_markup_percent' => '5.00',
                'silver_rounding_multiple' => 1000,
                'silver_min_order_amount' => 0,
                'gold_min_order_amount' => 1_000_000,
                'gold_pricing_threshold_amount' => 1_000_000,
                'gold_pricing_threshold_basis' => 'gold_candidate',
            ])
            ->assertRedirect(route('admin.settings.commerce.edit'))
            ->assertSessionHasErrors('silver_min_order_amount');

        $this->actingAs($admin)
            ->get(route('admin.settings.commerce.edit'))
            ->assertOk();
    }

    public function test_silver_cart_hides_minimum_when_rule_disabled(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, ['silverMinOrderEnabled' => false]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('Completa el pedido mínimo')
            ->assertSee('Continuar al checkout');
    }

    public function test_silver_cart_blocks_when_minimum_not_reached(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Completa el pedido mínimo')
            ->assertSee('Te faltan')
            ->assertSee('disabled', false)
            ->assertDontSee('Continuar al checkout');
    }

    public function test_silver_cart_allows_when_minimum_exactly_reached(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 105_000,
        ]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Pedido mínimo alcanzado')
            ->assertSee('Continuar al checkout')
            ->assertDontSee('>Completa el pedido mínimo<', false);
    }

    public function test_gold_cart_compact_when_both_rules_disabled(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('Te faltan')
            ->assertDontSee('Precios Oro aún no activados')
            ->assertSee('Ahorro Cliente Oro')
            ->assertSee('Continuar al checkout');
    }

    public function test_gold_cart_shows_minimum_pending_with_gold_prices(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 800_000,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Estado de tu pedido')
            ->assertSee('Completa el pedido mínimo')
            ->assertSee('para poder continuar')
            ->assertDontSee('Precios Oro aún no activados')
            ->assertSee('Ahorro Cliente Oro')
            ->assertDontSee('Continuar al checkout');
    }

    public function test_gold_cart_allows_checkout_when_minimum_reached(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 500_000,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 700_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Pedido mínimo alcanzado')
            ->assertDontSee('Precios Oro aún no activados')
            ->assertSee('Continuar al checkout')
            ->assertSee('Ahorro Cliente Oro');
    }

    public function test_gold_cart_blocks_when_minimum_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 1_500_000,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 500_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 700_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $response = $this->actingAs($user)->get(route('cart.index'));
        $pricing = app(CartService::class)->pricingResult();

        $this->assertTrue($pricing?->goldPricingApplied);
        $this->assertFalse($pricing?->checkoutAllowed());

        $response->assertOk()
            ->assertSee('Completa el pedido mínimo')
            ->assertSee('para poder continuar')
            ->assertDontSee('Precios Oro aún no activados')
            ->assertDontSee('Continuar al checkout');
    }

    public function test_gold_cart_864k_shows_progress_and_blocks_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 800_000,
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 1_000_000,
            'goldPricingThresholdEnabled' => false,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 96_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 9);

        $pricing = app(CartService::class)->pricingResult();
        $this->assertTrue($pricing?->goldPricingApplied);
        $this->assertSame(86_400_000, $pricing?->effectiveTotalCents);
        $this->assertFalse($pricing?->checkoutAllowed());
        $this->assertTrue($pricing?->minimumOrderDecision->enabled);
        $this->assertSame(13_600_000, $pricing?->minimumOrderDecision->missingAmountCents);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Estado de tu pedido')
            ->assertSee('Completa el pedido mínimo')
            ->assertSee('$136.000')
            ->assertSee('para poder continuar')
            ->assertSee('$864.000')
            ->assertSee('$1.000.000')
            ->assertSee('commerce-progress', false)
            ->assertSee('Completa el pedido mínimo')
            ->assertDontSee('Continuar al checkout');
    }

    public function test_gold_cart_960k_shows_progress_and_blocks_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 1_000_000,
            'goldPricingThresholdEnabled' => false,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 96_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 10);

        $pricing = app(CartService::class)->pricingResult();
        $this->assertSame(96_000_000, $pricing?->effectiveTotalCents);
        $this->assertFalse($pricing?->checkoutAllowed());
        $this->assertSame(4_000_000, $pricing?->minimumOrderDecision->missingAmountCents);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Completa el pedido mínimo')
            ->assertSee('$40.000')
            ->assertSee('commerce-progress', false)
            ->assertDontSee('Continuar al checkout');
    }

    public function test_gold_cart_exactly_one_million_allows_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 1_000_000,
            'goldPricingThresholdEnabled' => false,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 10);

        $pricing = app(CartService::class)->pricingResult();
        $this->assertTrue($pricing?->goldPricingApplied);
        $this->assertSame(100_000_000, $pricing?->effectiveTotalCents);
        $this->assertTrue($pricing?->checkoutAllowed());
        $this->assertSame('minimum_reached', $pricing?->minimumOrderDecision->reason);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Pedido mínimo alcanzado')
            ->assertSee('Continuar al checkout');
    }

    public function test_disabling_gold_threshold_keeps_gold_minimum_enabled(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 800_000,
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 1_000_000,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        app(CommercePricingRulesService::class)->publish(
            silverMarkupBasisPoints: 500,
            silverRoundingMultiple: 1000,
            actor: $admin,
            silverMinOrderEnabled: true,
            silverMinOrderAmount: 800_000,
            goldMinOrderEnabled: true,
            goldMinOrderAmount: 1_000_000,
            goldPricingThresholdEnabled: false,
            goldPricingThresholdAmount: 1_000_000,
            goldPricingThresholdBasis: GoldThresholdBasis::GoldCandidate,
        );

        $latest = CommercePricingRule::query()->orderByDesc('id')->firstOrFail();
        $this->assertTrue($latest->gold_min_order_enabled);
        $this->assertSame(1_000_000, $latest->gold_min_order_amount);
        $this->assertFalse($latest->gold_pricing_threshold_enabled);
        $this->assertTrue($latest->silver_min_order_enabled);
        $this->assertSame(800_000, $latest->silver_min_order_amount);
    }

    public function test_gold_cart_shows_savings_when_minimum_met(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 500_000,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 500_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 700_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Pedido mínimo alcanzado')
            ->assertSee('Ahorro Cliente Oro')
            ->assertSee('Precio Oro aplicado')
            ->assertSee('Continuar al checkout');
    }

    public function test_checkout_blocks_visually_when_not_allowed(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Aún no puedes finalizar el pedido')
            ->assertSee('Volver al carrito')
            ->assertDontSee('Solo cotizar')
            ->assertDontSee('Pagar ahora');
    }

    public function test_checkout_shows_gold_minimum_reached(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => true,
            'goldMinOrderAmount' => 500_000,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 700_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Pedido mínimo alcanzado')
            ->assertDontSee('Se aplicarán precios Plata')
            ->assertSee('Ahorro Cliente Oro')
            ->assertSee('Solo cotizar');
    }

    public function test_checkout_shows_gold_pricing_with_savings(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 100_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 200_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertDontSee('Precios Oro aún no activados')
            ->assertDontSee('Se aplicarán precios Plata')
            ->assertSee('Ahorro Cliente Oro')
            ->assertSee('Solo cotizar');
    }
}
