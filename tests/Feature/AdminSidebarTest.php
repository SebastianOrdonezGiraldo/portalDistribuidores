<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_shows_companies_and_access_accounts_sections(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/distributors')
            ->assertOk()
            ->assertSee('Empresas distribuidoras')
            ->assertSee('Cuentas de acceso')
            ->assertSee('Acceso y seguridad')
            ->assertSee('Mi perfil')
            ->assertDontSee('>Usuarios<', false);
    }

    public function test_distributor_sidebar_is_unchanged(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor Sidebar',
            'status' => 'active',
        ]);

        $distributorUser = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
        ]);

        $this->actingAs($distributorUser)
            ->get('/empresa')
            ->assertOk()
            ->assertDontSee('Empresas distribuidoras')
            ->assertDontSee('Cuentas de acceso');
    }

    public function test_distributor_cannot_access_admin_users_or_distributors(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($distributorUser)
            ->get('/admin/distributors')
            ->assertForbidden();
    }
}
