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

/**
 * Live cart commercial sync (JSON PATCH).
 *
 * Manual QA (browser) — required, not covered by Feature tests:
 * 1. Serial queue: change qty while a request is in flight; only one PATCH at a time;
 *    a change during flight triggers a second sync; final qty matches last edit.
 * 2. Dirty CTA: while pending, checkout shows "Actualizando carrito…" and stays disabled.
 * 3. 422 stock: rejected qty restores confirmed amount; checkout follows canonical state.
 * 4. Network loss: change qty → DevTools offline before response → checkout blocked,
 *    "Actualizar cantidades" reappears → restore network → manual update → synced.
 */
class CartLiveUpdateTest extends TestCase
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

    public function test_json_below_silver_minimum_blocks_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 1_000_000,
        ]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);
        $lineKey = (string) app(CartService::class)->items()->firstOrFail()['line_key'];

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), [
                'quantities' => [$lineKey => 1],
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('empty', false)
            ->assertJsonPath('pricing.checkout_allowed', false)
            ->assertJsonStructure([
                'pricing' => ['checkout_allowed', 'gold_pricing_applied', 'effective_total_cents', 'gold_savings_cents'],
                'lines' => [
                    $lineKey => [
                        'qty',
                        'unit_price_cents',
                        'silver_unit_price_cents',
                        'base_unit_price_cents',
                        'subtotal_cents',
                        'pricing_html',
                        'subtotal_html',
                    ],
                ],
                'html' => ['commercial_status', 'order_summary', 'checkout_cta'],
            ]);

        $this->assertIsInt($response->json('pricing.effective_total_cents'));
        $this->assertStringContainsString('Completa el pedido mínimo', $response->json('html.checkout_cta'));
        $this->assertStringNotContainsString('Continuar al checkout', $response->json('html.commercial_status'));
    }

    public function test_json_exactly_at_silver_minimum_allows_checkout(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'silverMinOrderEnabled' => true,
            'silverMinOrderAmount' => 105_000,
        ]);

        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);
        $lineKey = (string) app(CartService::class)->items()->firstOrFail()['line_key'];

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), [
                'quantities' => [$lineKey => 1],
            ])
            ->assertOk()
            ->assertJsonPath('pricing.checkout_allowed', true)
            ->assertJsonPath('pricing.effective_total_cents', 10_500_000);

        $cta = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), ['quantities' => [$lineKey => 1]])
            ->json('html.checkout_cta');

        $this->assertStringContainsString('Continuar al checkout', $cta);
    }

    public function test_json_gold_crosses_threshold_updates_all_line_prices(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 500_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $productA = Product::factory()->create(['price' => 200_000, 'stock' => 20, 'is_active' => true]);
        $productB = Product::factory()->create(['price' => 200_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        $cart = app(CartService::class);
        $cart->add($productA, 1);
        $cart->add($productB, 1);

        $keys = $cart->items()->pluck('line_key')->map(fn ($k) => (string) $k)->all();
        $quantities = array_fill_keys($keys, 2);

        $below = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), [
                'quantities' => array_fill_keys($keys, 1),
            ]);

        $below->assertOk()->assertJsonPath('pricing.gold_pricing_applied', false);

        foreach ($keys as $key) {
            $this->assertSame(21_000_000, $below->json("lines.$key.unit_price_cents"));
        }

        $above = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), [
                'quantities' => $quantities,
            ]);

        $above->assertOk()
            ->assertJsonPath('pricing.gold_pricing_applied', true);

        $this->assertGreaterThan(0, (int) $above->json('pricing.gold_savings_cents'));

        foreach ($keys as $key) {
            $this->assertSame(20_000_000, $above->json("lines.$key.unit_price_cents"));
            $this->assertStringContainsString('Precio Oro aplicado', $above->json("lines.$key.pricing_html"));
        }
    }

    public function test_json_gold_loses_threshold_reverts_lines_to_silver(): void
    {
        $admin = User::factory()->admin()->create();
        $this->publishRules($admin, [
            'goldPricingThresholdEnabled' => true,
            'goldPricingThresholdAmount' => 500_000,
        ]);

        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 400_000, 'stock' => 20, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 2);
        $lineKey = (string) app(CartService::class)->items()->firstOrFail()['line_key'];

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), ['quantities' => [$lineKey => 2]])
            ->assertOk()
            ->assertJsonPath('pricing.gold_pricing_applied', true)
            ->assertJsonPath("lines.$lineKey.unit_price_cents", 40_000_000);

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), ['quantities' => [$lineKey => 1]])
            ->assertOk()
            ->assertJsonPath('pricing.gold_pricing_applied', false)
            ->assertJsonPath("lines.$lineKey.unit_price_cents", 42_000_000);
    }

    public function test_stock_error_returns_422_with_canonical_state_without_partial_mutation(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 2, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);
        $lineKey = (string) app(CartService::class)->items()->firstOrFail()['line_key'];

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), [
                'quantities' => [$lineKey => 5],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonStructure(['message', 'state']);

        $this->assertSame(1, (int) app(CartService::class)->items()->firstOrFail()['qty']);
        $this->assertSame(1, $response->json("state.lines.$lineKey.qty"));
        $this->assertIsInt($response->json('state.pricing.effective_total_cents'));
    }

    public function test_zero_quantity_removes_line_and_empty_cart_returns_redirect(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 2);
        $lineKey = (string) app(CartService::class)->items()->firstOrFail()['line_key'];

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson(route('cart.update'), [
                'quantities' => [$lineKey => 0],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('empty', true)
            ->assertJsonPath('redirect_url', route('cart.index', absolute: false));

        $this->assertTrue(app(CartService::class)->items()->isEmpty());
    }

    public function test_html_request_keeps_legacy_redirect(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100_000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);
        $lineKey = (string) app(CartService::class)->items()->firstOrFail()['line_key'];

        $this->actingAs($user)
            ->from(route('cart.index'))
            ->patch(route('cart.update'), [
                'quantities' => [$lineKey => 2],
                'redirect_checkout' => '0',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status');

        $this->assertSame(2, (int) app(CartService::class)->items()->firstOrFail()['qty']);
    }
}
