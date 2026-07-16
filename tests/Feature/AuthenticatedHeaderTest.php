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

    public function test_distributor_header_contains_catalog_search_in_its_main_row(): void
    {
        $user = $this->distributorUser();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertSee('data-global-catalog-search', false)
            ->assertSee('action="'.route('catalog.index').'"', false)
            ->assertSee('name="term"', false)
            ->assertSee('Buscar producto, SKU, marca o categoría');

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
