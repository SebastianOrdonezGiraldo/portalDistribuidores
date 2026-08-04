<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Orders\Pricing\CommercePricingRulesService;
use App\Modules\Orders\Services\Cart\CartService;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCommerceSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function commercePayload(array $overrides = []): array
    {
        return array_merge([
            'silver_markup_percent' => '5.00',
            'silver_rounding_multiple' => 1000,
            'silver_min_order_amount' => 1_000_000,
            'gold_min_order_amount' => 1_000_000,
            'gold_pricing_threshold_amount' => 1_000_000,
            'gold_pricing_threshold_basis' => 'gold_candidate',
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_admin_can_view_commerce_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.commerce.edit'))
            ->assertOk()
            ->assertSee('Reglas comerciales')
            ->assertSee('Incremento del precio Plata sobre el precio Oro')
            ->assertSee('Historial reciente');
    }

    public function test_distributor_receives_forbidden(): void
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);

        $this->actingAs($user)
            ->get(route('admin.settings.commerce.edit'))
            ->assertForbidden();
    }

    public function test_page_shows_current_rule_and_history(): void
    {
        $admin = User::factory()->admin()->create();
        app(CommercePricingRulesService::class)->publish(650, 500, $admin);

        $this->actingAs($admin)
            ->get(route('admin.settings.commerce.edit'))
            ->assertOk()
            ->assertSee('6.50')
            ->assertSee($admin->name);
    }

    public function test_admin_can_save_six_percent(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.commerce.edit'))
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '6.00',
            ]))
            ->assertRedirect(route('admin.settings.commerce.edit'))
            ->assertSessionHas('status');

        $latest = CommercePricingRule::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame(600, $latest->silver_markup_basis_points);
        $this->assertSame(1000, $latest->silver_rounding_multiple);
        $this->assertSame($admin->id, $latest->created_by_id);
    }

    public function test_saving_six_percent_updates_silver_catalog_price(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        Product::factory()->create([
            'name' => 'Producto prueba reglas',
            'price' => 96000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '6.00',
            ]))
            ->assertRedirect(route('admin.settings.commerce.edit'));

        $this->actingAs($user)
            ->get(route('catalog.index', ['term' => 'Producto prueba reglas']))
            ->assertOk()
            ->assertSee('$102.000', false);

        $goldDistributor = Distributor::factory()->gold()->create();
        $goldUser = User::factory()->create(['distributor_id' => $goldDistributor->id]);

        $this->actingAs($goldUser)
            ->get(route('catalog.index', ['term' => 'Producto prueba reglas']))
            ->assertOk()
            ->assertSee('$96.000', false);
    }

    public function test_admin_can_save_five_percent(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('commerce_pricing_rules')->delete();

        $this->actingAs($admin)
            ->from(route('admin.settings.commerce.edit'))
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '5',
            ]))
            ->assertRedirect(route('admin.settings.commerce.edit'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('commerce_pricing_rules', [
            'silver_markup_basis_points' => 500,
            'silver_rounding_multiple' => 1000,
            'created_by_id' => $admin->id,
        ]);
    }

    public function test_admin_can_save_six_point_fifty_with_comma(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '6,50',
                'silver_rounding_multiple' => 500,
            ]))
            ->assertRedirect(route('admin.settings.commerce.edit'));

        $latest = CommercePricingRule::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame(650, $latest->silver_markup_basis_points);
        $this->assertSame(500, $latest->silver_rounding_multiple);
    }

    public function test_rejects_negative_percent(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.commerce.edit'))
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '-1',
            ]))
            ->assertRedirect(route('admin.settings.commerce.edit'))
            ->assertSessionHasErrors('silver_markup_percent');
    }

    public function test_rejects_percent_over_100(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.commerce.edit'))
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '101',
            ]))
            ->assertSessionHasErrors('silver_markup_percent');
    }

    public function test_rejects_more_than_two_decimals(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.commerce.edit'))
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '5.125',
            ]))
            ->assertSessionHasErrors('silver_markup_percent');
    }

    public function test_rejects_rounding_outside_options(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.commerce.edit'))
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_markup_percent' => '5',
                'silver_rounding_multiple' => 250,
            ]))
            ->assertSessionHasErrors('silver_rounding_multiple');
    }

    public function test_no_new_version_when_unchanged(): void
    {
        $admin = User::factory()->admin()->create();
        $before = CommercePricingRule::query()->count();

        $this->actingAs($admin)
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload())
            ->assertRedirect(route('admin.settings.commerce.edit'))
            ->assertSessionHas('status');

        $this->assertSame($before, CommercePricingRule::query()->count());
    }

    public function test_admin_can_save_minimum_order_and_gold_threshold_rules(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.settings.commerce.update'), $this->commercePayload([
                'silver_min_order_enabled' => '1',
                'silver_min_order_amount' => 500_000,
                'gold_min_order_enabled' => '1',
                'gold_min_order_amount' => 800_000,
                'gold_pricing_threshold_enabled' => '1',
                'gold_pricing_threshold_amount' => 1_000_000,
                'gold_pricing_threshold_basis' => 'silver_candidate',
            ]))
            ->assertRedirect(route('admin.settings.commerce.edit'));

        $latest = CommercePricingRule::query()->orderByDesc('id')->firstOrFail();
        $this->assertTrue($latest->silver_min_order_enabled);
        $this->assertSame(500_000, $latest->silver_min_order_amount);
        $this->assertTrue($latest->gold_min_order_enabled);
        $this->assertSame(800_000, $latest->gold_min_order_amount);
        $this->assertTrue($latest->gold_pricing_threshold_enabled);
        $this->assertSame(1_000_000, $latest->gold_pricing_threshold_amount);
        $this->assertSame('silver_candidate', $latest->gold_pricing_threshold_basis);
    }

    public function test_admin_sidebar_shows_commerce_rules_link(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Reglas comerciales')
            ->assertSee(route('admin.settings.commerce.edit'), false);
    }

    public function test_distributor_sidebar_does_not_show_commerce_rules(): void
    {
        $distributor = Distributor::factory()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);

        $this->actingAs($user)
            ->get('/empresa')
            ->assertOk()
            ->assertDontSee('Reglas comerciales');
    }

    public function test_publishing_seven_percent_updates_silver_cart(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);
        $this->assertSame(105000.0, app(CartService::class)->items()->firstOrFail()['unit_price']);

        app(CommercePricingRulesService::class)->publish(700, 1000, $admin);

        $this->actingAs($user->fresh());
        $this->assertSame(107000.0, app(CartService::class)->items()->firstOrFail()['unit_price']);
    }

    public function test_gold_cart_keeps_base_after_rule_change(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);
        $product = Product::factory()->create(['price' => 100000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        app(CommercePricingRulesService::class)->publish(700, 1000, $admin);

        $this->actingAs($user->fresh());
        $line = app(CartService::class)->items()->firstOrFail();
        $this->assertSame(DistributorTier::Gold, $line['tier']);
        $this->assertSame(100000.0, $line['unit_price']);
        $this->assertSame(107000.0, $line['silver_unit_price']);
    }
}
