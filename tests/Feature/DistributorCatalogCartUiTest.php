<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorCatalogCartUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_catalog_renders_cart_target_and_empty_badge(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor UI',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk();
        $response->assertSee('data-cart-target', false);
        $response->assertSee('data-cart-badge', false);
        $response->assertSee('>0<', false);
    }
}
