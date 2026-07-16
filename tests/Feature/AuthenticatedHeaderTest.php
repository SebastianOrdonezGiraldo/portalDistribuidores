<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_catalog_moves_distributor_registration_to_header_and_uses_compact_whatsapp_access(): void
    {
        $response = $this->get(route('catalog.index'));

        $response
            ->assertOk()
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('Ser distribuidor')
            ->assertSee('catalog-support-bubble--whatsapp', false)
            ->assertDontSee('¿Quieres ser distribuidor?')
            ->assertDontSee('¿Necesitas ayuda?');
    }

    public function test_distributor_header_contains_catalog_search_in_its_main_row(): void
    {
        $user = $this->distributorUser();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertSee('data-global-catalog-search', false)
            ->assertSee('action="'.route('catalog.index').'"', false)
            ->assertSee('name="term"', false)
            ->assertSee('Buscar producto, SKU, marca o categoría')
            ->assertDontSee('Ser distribuidor');

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression(
            '/<header[^>]*>.*data-global-catalog-search.*<\/header>/s',
            $html
        );
    }

    public function test_authenticated_header_does_not_duplicate_the_sidebar_logo(): void
    {
        $user = $this->distributorUser();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression('/<aside[^>]*>.*import-corporal-logo\.png.*<\/aside>/s', $html);
        $this->assertMatchesRegularExpression('/<header[^>]*>.*<\/header>/s', $html);

        preg_match('/<header[^>]*>.*?<\/header>/s', $html, $header);
        $this->assertStringNotContainsString('import-corporal-logo.png', $header[0] ?? '');
    }

    public function test_admin_header_uses_the_centered_global_order_search(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertSee('data-global-admin-search', false)
            ->assertSee('action="'.route('admin.orders.index').'"', false)
            ->assertSee('Buscar CTC, cliente, contacto o correo');

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression(
            '/<header[^>]*>.*data-global-admin-search.*<\/header>/s',
            $html
        );
    }

    public function test_distributor_sidebar_exposes_a_desktop_collapse_control_and_linked_icons(): void
    {
        $user = $this->distributorUser();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertSee('data-distributor-sidebar', false)
            ->assertSee('data-sidebar-collapse', false)
            ->assertSee('data-sidebar-nav-link', false)
            ->assertSee('data-app-content', false);

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression(
            '/<a[^>]*data-sidebar-nav-link[^>]*href="[^"]+"[^>]*>.*?<svg/s',
            $html
        );
    }

    private function distributorUser(): User
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor Header',
            'status' => 'active',
        ]);

        return User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);
    }
}
