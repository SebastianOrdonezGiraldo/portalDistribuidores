<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminDistributorTierUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_badge_renders_silver(): void
    {
        $html = Blade::render('<x-ui.tier-badge tier="plata" />');

        $this->assertStringContainsString('ICM Plata', $html);
        $this->assertStringContainsString('bg-slate-100', $html);
    }

    public function test_tier_badge_renders_gold(): void
    {
        $html = Blade::render('<x-ui.tier-badge :tier="$tier" />', [
            'tier' => DistributorTier::Gold,
        ]);

        $this->assertStringContainsString('ICM Oro', $html);
        $this->assertStringContainsString('bg-amber-50', $html);
    }

    public function test_tier_badge_falls_back_to_silver_when_null(): void
    {
        $html = Blade::render('<x-ui.tier-badge :tier="null" />');

        $this->assertStringContainsString('ICM Plata', $html);
    }

    public function test_tier_select_renders_current_option_selected(): void
    {
        $html = Blade::render('<x-ui.tier-select name="tier" value="oro" />');

        $this->assertStringContainsString('value="oro"', $html);
        $this->assertStringContainsString('ICM Oro', $html);
        $this->assertMatchesRegularExpression('/value="oro"[^>]*selected/s', $html);
    }

    public function test_tier_select_respects_old_input(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->gold()->create();

        $response = $this->actingAs($admin)
            ->withSession(['_old_input' => ['tier' => 'plata']])
            ->get(route('admin.distributors.edit', $distributor));

        $response->assertOk();
        $response->assertSee('value="plata"', false);
        $response->assertSee('selected', false);
        $this->assertMatchesRegularExpression(
            '/value="plata"[^>]*selected/s',
            $response->getContent(),
        );
    }

    public function test_tier_change_form_menu_submits_opposite_tier(): void
    {
        $distributor = Distributor::factory()->silver()->create();

        $html = Blade::render(
            '<x-admin.distributors.tier-change-form :distributor="$distributor" variant="menu" />',
            ['distributor' => $distributor],
        );

        $this->assertStringContainsString(route('admin.distributors.tier.update', $distributor), $html);
        $this->assertStringContainsString('name="tier"', $html);
        $this->assertStringContainsString('value="oro"', $html);
        $this->assertStringContainsString('Cambiar a ICM Oro', $html);
        $this->assertStringContainsString('utilizarán precios Oro', $html);
    }

    public function test_tier_change_form_card_uses_patch_route_with_selectable_tier(): void
    {
        $distributor = Distributor::factory()->gold()->create();

        $html = Blade::render(
            '<x-admin.distributors.tier-change-form :distributor="$distributor" variant="card" selectable />',
            ['distributor' => $distributor],
        );

        $this->assertStringContainsString(route('admin.distributors.tier.update', $distributor), $html);
        $this->assertStringContainsString('Actualizar nivel', $html);
        $this->assertStringContainsString('distributor-tier-select', $html);
        $this->assertStringContainsString('x-bind:data-confirm', $html);
        $this->assertStringContainsString('precios hist', $html);
    }

    public function test_tier_card_shows_audit_information_when_present(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->gold()->create([
            'tier_changed_at' => now(),
            'tier_changed_by_id' => $admin->id,
        ]);
        $distributor->load('tierChangedBy');

        $html = Blade::render(
            '<x-admin.distributors.tier-card :distributor="$distributor" />',
            ['distributor' => $distributor],
        );

        $this->assertStringContainsString('Último cambio', $html);
        $this->assertStringContainsString($admin->name, $html);
        $this->assertStringContainsString($admin->email, $html);
        $this->assertStringContainsString('ICM Oro', $html);
    }

    public function test_tier_card_shows_fallback_when_audit_is_missing(): void
    {
        $distributor = Distributor::factory()->silver()->create([
            'tier_changed_at' => null,
            'tier_changed_by_id' => null,
        ]);

        $html = Blade::render(
            '<x-admin.distributors.tier-card :distributor="$distributor" />',
            ['distributor' => $distributor],
        );

        $this->assertStringContainsString('Sin cambios registrados', $html);
        $this->assertStringContainsString('Aún no aplica', $html);
    }

    public function test_index_lists_silver_and_gold_badges(): void
    {
        $admin = User::factory()->admin()->create();
        Distributor::factory()->silver()->create(['name' => 'Distribuidor Plata UI']);
        Distributor::factory()->gold()->create(['name' => 'Distribuidor Oro UI']);

        $this->actingAs($admin)
            ->get('/admin/distributors?q=UI')
            ->assertOk()
            ->assertSee('ICM Plata')
            ->assertSee('ICM Oro')
            ->assertSee('Nivel');
    }

    public function test_index_filters_by_silver_tier(): void
    {
        $admin = User::factory()->admin()->create();
        Distributor::factory()->silver()->create(['name' => 'Filtro Plata Dist']);
        Distributor::factory()->gold()->create(['name' => 'Filtro Oro Dist']);

        $response = $this->actingAs($admin)->get('/admin/distributors?tier=plata&q=Filtro');

        $response->assertOk();
        $response->assertSee('Filtro Plata Dist');
        $response->assertDontSee('Filtro Oro Dist');
        $response->assertViewHas('metrics', fn (array $metrics) => (int) $metrics['silver_distributors'] === 1
            && (int) $metrics['gold_distributors'] === 0);
    }

    public function test_index_filters_by_gold_tier(): void
    {
        $admin = User::factory()->admin()->create();
        Distributor::factory()->silver()->create(['name' => 'Gold Filter Plata']);
        Distributor::factory()->gold()->create(['name' => 'Gold Filter Oro']);

        $response = $this->actingAs($admin)->get('/admin/distributors?tier=oro&q=Gold Filter');

        $response->assertOk();
        $response->assertSee('Gold Filter Oro');
        $response->assertDontSee('Gold Filter Plata');
        $response->assertViewHas('metrics', fn (array $metrics) => (int) $metrics['gold_distributors'] === 1);
    }

    public function test_edit_form_shows_tier_card_and_patch_route(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create(['name' => 'Edit Tier Dist']);

        $this->actingAs($admin)
            ->get(route('admin.distributors.edit', $distributor))
            ->assertOk()
            ->assertSee('Nivel comercial')
            ->assertSee('Actualizar nivel')
            ->assertSee(route('admin.distributors.tier.update', $distributor), false)
            ->assertSee('Sin cambios registrados');
    }

    public function test_create_form_informs_default_silver_tier(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.distributors.create'))
            ->assertOk()
            ->assertSee('El distribuidor será creado inicialmente como ICM Plata.')
            ->assertDontSee('Actualizar nivel');
    }

    public function test_index_quick_action_uses_tier_update_route(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create(['name' => 'Quick Tier Action Dist']);

        $this->actingAs($admin)
            ->get('/admin/distributors?q=Quick Tier Action')
            ->assertOk()
            ->assertSee(route('admin.distributors.tier.update', $distributor), false)
            ->assertSee('Cambiar a ICM Oro');
    }

    public function test_edit_form_shows_tier_validation_error(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();

        $this->actingAs($admin)
            ->from(route('admin.distributors.edit', $distributor))
            ->patch(route('admin.distributors.tier.update', $distributor), ['tier' => 'diamante'])
            ->assertRedirect(route('admin.distributors.edit', $distributor))
            ->assertSessionHasErrors('tier');

        $this->actingAs($admin)
            ->get(route('admin.distributors.edit', $distributor))
            ->assertOk()
            ->assertSee('Nivel comercial');
    }

    public function test_non_admin_cannot_access_distributor_tier_ui_routes(): void
    {
        $distributorUser = User::factory()->create();
        $distributor = Distributor::factory()->silver()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/distributors')
            ->assertForbidden();

        $this->actingAs($distributorUser)
            ->get(route('admin.distributors.edit', $distributor))
            ->assertForbidden();
    }
}
