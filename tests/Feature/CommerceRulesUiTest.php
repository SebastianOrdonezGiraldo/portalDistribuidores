<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
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
            ->assertSee('Tus precios Oro están activos.')
            ->assertSee('Ahorro Cliente Oro')
            ->assertSee('Continuar al checkout');
    }

    public function test_gold_cart_below_threshold_pays_silver_and_keeps_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 800_000,
            'goldMinOrderEnabled' => false,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 96_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 7);

        $pricing = app(CartService::class)->pricingResult();
        $this->assertFalse($pricing?->goldPricingApplied);
        $this->assertTrue($pricing?->checkoutAllowed());
        $this->assertSame(0, $pricing?->goldSavingsCents);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Activa tus precios Oro')
            ->assertSee('$328.000')
            ->assertSee('para alcanzar')
            ->assertSee('$1.000.000')
            ->assertSee('Si continúas ahora, este pedido se procesará con precios Plata.')
            ->assertSee('commerce-progress', false)
            ->assertSee('Precio Plata aplicado')
            ->assertSee('Continuar al checkout')
            ->assertDontSee('Ahorro Cliente Oro')
            ->assertDontSee('Precio Oro aplicado');
    }

    public function test_gold_cart_960k_still_pays_silver_on_gold_candidate_basis(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => false,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 96_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 10);

        $pricing = app(CartService::class)->pricingResult();
        $this->assertFalse($pricing?->goldPricingApplied);
        $this->assertSame(101_000_000, $pricing?->effectiveTotalCents);
        $this->assertTrue($pricing?->checkoutAllowed());
        $this->assertSame(4_000_000, $pricing?->goldPricingDecision->missingAmountCents);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Activa tus precios Oro')
            ->assertSee('$40.000')
            ->assertSee('Si continúas ahora, este pedido se procesará con precios Plata.')
            ->assertSee('commerce-progress', false)
            ->assertSee('Precio Plata aplicado')
            ->assertSee('Continuar al checkout')
            ->assertDontSee('Ahorro Cliente Oro')
            ->assertDontSee('Precio Oro aplicado');
    }

    public function test_gold_cart_above_threshold_applies_gold_and_savings(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => false,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 96_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 11);

        $pricing = app(CartService::class)->pricingResult();
        $this->assertTrue($pricing?->goldPricingApplied);
        $this->assertSame(105_600_000, $pricing?->effectiveTotalCents);
        $this->assertTrue($pricing?->checkoutAllowed());
        $this->assertSame(5_500_000, $pricing?->goldSavingsCents);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Precios Oro activados')
            ->assertSee('Tu pedido ya alcanzó el monto requerido y ahora aplica precios Oro.')
            ->assertSee('Ahorro Cliente Oro')
            ->assertSee('Precio Oro aplicado')
            ->assertDontSee('Precio Plata aplicado')
            ->assertDontSee('Si continúas ahora, este pedido se procesará con precios Plata.')
            ->assertSee('Continuar al checkout');
    }

    public function test_gold_cart_exact_threshold_applies_gold(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => false,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 10);

        $pricing = app(CartService::class)->pricingResult();
        $this->assertTrue($pricing?->goldPricingApplied);
        $this->assertTrue($pricing?->checkoutAllowed());

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Precios Oro activados')
            ->assertSee('Tu pedido ya alcanzó el monto requerido y ahora aplica precios Oro.')
            ->assertSee('Continuar al checkout');
    }

    public function test_silver_cart_still_blocked_under_800k_minimum(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 800_000,
            'goldMinOrderEnabled' => false,
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Completa el pedido mínimo')
            ->assertDontSee('Continuar al checkout')
            ->assertDontSee('Sube a Nivel Oro')
            ->assertDontSee('Ahorrarías', false)
            ->assertDontSee('Solicitar ascenso a Oro');
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
            ->assertDontSee('Pagar ahora')
            ->assertDontSee('Sube a Nivel Oro')
            ->assertDontSee('Ahorrarías', false)
            ->assertDontSee('Solicitar ascenso a Oro');
    }

    public function test_checkout_warns_when_gold_pays_silver(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => false,
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
            ->assertSee('Se aplicarán precios Plata')
            ->assertSee('Solo cotizar')
            ->assertDontSee('Ahorro Cliente Oro');
    }

    public function test_checkout_shows_gold_pricing_applied(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldMinOrderEnabled' => false,
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
            ->assertSee('Precios Oro aplicados.')
            ->assertSee('Ahorro Cliente Oro')
            ->assertSee('Solo cotizar');
    }
}
