<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Shared\Enums\CompanyRole;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios para los métodos de permisos del modelo User.
 *
 * Cubre todas las combinaciones de UserRole × CompanyRole que el dominio
 * contempla, asegurando que ninguna regla de negocio se rompa en silencio.
 */
class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────────
    // isAdmin / isDistributor
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_role_is_recognized(): void
    {
        $user = User::factory()->admin()->make();

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isDistributor());
    }

    public function test_distributor_role_is_recognized(): void
    {
        $user = User::factory()->make(['role' => UserRole::Distributor]);

        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isDistributor());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canCreateOrders
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_always_create_orders(): void
    {
        $user = User::factory()->admin()->make();

        $this->assertTrue($user->canCreateOrders());
    }

    public function test_distributor_without_company_role_can_create_orders(): void
    {
        $user = User::factory()->make(['company_role' => null]);

        $this->assertTrue($user->canCreateOrders());
    }

    public function test_distributor_with_admin_empresa_role_can_create_orders(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::AdminEmpresa]);

        $this->assertTrue($user->canCreateOrders());
    }

    public function test_distributor_with_usuario_comercial_role_can_create_orders(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::UsuarioComercial]);

        $this->assertTrue($user->canCreateOrders());
    }

    public function test_distributor_with_solo_lectura_cannot_create_orders(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::SoloLectura]);

        $this->assertFalse($user->canCreateOrders());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canEditCompany
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_empresa_can_edit_company(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::AdminEmpresa]);

        $this->assertTrue($user->canEditCompany());
    }

    public function test_usuario_comercial_cannot_edit_company(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::UsuarioComercial]);

        $this->assertFalse($user->canEditCompany());
    }

    public function test_distributor_without_company_role_cannot_edit_company(): void
    {
        $user = User::factory()->make(['company_role' => null]);

        $this->assertFalse($user->canEditCompany());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canReorder
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_always_reorder(): void
    {
        $user = User::factory()->admin()->make();

        $this->assertTrue($user->canReorder());
    }

    public function test_distributor_without_company_role_can_reorder(): void
    {
        $user = User::factory()->make(['company_role' => null]);

        $this->assertTrue($user->canReorder());
    }

    public function test_admin_empresa_can_reorder(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::AdminEmpresa]);

        $this->assertTrue($user->canReorder());
    }

    public function test_usuario_comercial_can_reorder(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::UsuarioComercial]);

        $this->assertTrue($user->canReorder());
    }

    public function test_solo_lectura_cannot_reorder(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::SoloLectura]);

        $this->assertFalse($user->canReorder());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canManageLists
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_always_manage_lists(): void
    {
        $user = User::factory()->admin()->make();

        $this->assertTrue($user->canManageLists());
    }

    public function test_distributor_without_company_role_can_manage_lists(): void
    {
        $user = User::factory()->make(['company_role' => null]);

        $this->assertTrue($user->canManageLists());
    }

    public function test_solo_lectura_cannot_manage_lists(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::SoloLectura]);

        $this->assertFalse($user->canManageLists());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canManageBranches
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_empresa_can_manage_branches(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::AdminEmpresa]);

        $this->assertTrue($user->canManageBranches());
    }

    public function test_usuario_comercial_cannot_manage_branches(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::UsuarioComercial]);

        $this->assertFalse($user->canManageBranches());
    }

    public function test_distributor_without_company_role_cannot_manage_branches(): void
    {
        $user = User::factory()->make(['company_role' => null]);

        $this->assertFalse($user->canManageBranches());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canApproveOrders
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_empresa_can_approve_orders(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::AdminEmpresa]);

        $this->assertTrue($user->canApproveOrders());
    }

    public function test_usuario_comercial_cannot_approve_orders(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::UsuarioComercial]);

        $this->assertFalse($user->canApproveOrders());
    }

    public function test_solo_lectura_cannot_approve_orders(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::SoloLectura]);

        $this->assertFalse($user->canApproveOrders());
    }

    public function test_distributor_without_company_role_cannot_approve_orders(): void
    {
        $user = User::factory()->make(['company_role' => null]);

        $this->assertFalse($user->canApproveOrders());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // orderRequiresApproval
    // ──────────────────────────────────────────────────────────────────────────

    public function test_usuario_comercial_orders_require_approval(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::UsuarioComercial]);

        $this->assertTrue($user->orderRequiresApproval());
    }

    public function test_admin_empresa_orders_do_not_require_approval(): void
    {
        $user = User::factory()->make(['company_role' => CompanyRole::AdminEmpresa]);

        $this->assertFalse($user->orderRequiresApproval());
    }

    public function test_distributor_without_company_role_orders_do_not_require_approval(): void
    {
        $user = User::factory()->make(['company_role' => null]);

        $this->assertFalse($user->orderRequiresApproval());
    }

    public function test_admin_user_orders_never_require_approval(): void
    {
        $user = User::factory()->admin()->make();

        $this->assertFalse($user->orderRequiresApproval());
    }

    public function test_solo_lectura_orders_do_not_require_approval(): void
    {
        // SoloLectura no puede crear órdenes, pero no las marca como pendientes de aprobación.
        $user = User::factory()->make(['company_role' => CompanyRole::SoloLectura]);

        $this->assertFalse($user->orderRequiresApproval());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // isCompanyAdmin
    // ──────────────────────────────────────────────────────────────────────────

    public function test_is_company_admin_requires_distributor_role_and_admin_empresa(): void
    {
        $admin = User::factory()->admin()->make(['company_role' => CompanyRole::AdminEmpresa]);
        $distAdminEmpresa = User::factory()->make(['company_role' => CompanyRole::AdminEmpresa]);
        $distNoRole = User::factory()->make(['company_role' => null]);

        $this->assertFalse($admin->isCompanyAdmin(), 'Un admin global no es company admin');
        $this->assertTrue($distAdminEmpresa->isCompanyAdmin());
        $this->assertFalse($distNoRole->isCompanyAdmin());
    }
}
