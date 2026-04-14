<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function test_active_distributor_can_create_orders(): void
    {
        $user = User::factory()->make(['is_active' => true]);

        $this->assertTrue($user->canCreateOrders());
    }

    public function test_inactive_distributor_cannot_create_orders(): void
    {
        $user = User::factory()->inactive()->make();

        $this->assertFalse($user->canCreateOrders());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canEditCompany
    // ──────────────────────────────────────────────────────────────────────────

    public function test_active_distributor_with_distributor_id_can_edit_company(): void
    {
        $user = User::factory()->make(['distributor_id' => 1, 'is_active' => true]);

        $this->assertTrue($user->canEditCompany());
    }

    public function test_distributor_without_distributor_id_cannot_edit_company(): void
    {
        $user = User::factory()->make(['distributor_id' => null]);

        $this->assertFalse($user->canEditCompany());
    }

    public function test_inactive_distributor_cannot_edit_company(): void
    {
        $user = User::factory()->inactive()->make(['distributor_id' => 1]);

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

    public function test_active_distributor_can_reorder(): void
    {
        $user = User::factory()->make(['is_active' => true]);

        $this->assertTrue($user->canReorder());
    }

    public function test_inactive_distributor_cannot_reorder(): void
    {
        $user = User::factory()->inactive()->make();

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

    public function test_active_distributor_can_manage_lists(): void
    {
        $user = User::factory()->make(['is_active' => true]);

        $this->assertTrue($user->canManageLists());
    }

    public function test_inactive_distributor_cannot_manage_lists(): void
    {
        $user = User::factory()->inactive()->make();

        $this->assertFalse($user->canManageLists());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canManageBranches
    // ──────────────────────────────────────────────────────────────────────────

    public function test_active_distributor_with_distributor_id_can_manage_branches(): void
    {
        $user = User::factory()->make(['distributor_id' => 1, 'is_active' => true]);

        $this->assertTrue($user->canManageBranches());
    }

    public function test_distributor_without_distributor_id_cannot_manage_branches(): void
    {
        $user = User::factory()->make(['distributor_id' => null]);

        $this->assertFalse($user->canManageBranches());
    }

    public function test_inactive_distributor_cannot_manage_branches(): void
    {
        $user = User::factory()->inactive()->make(['distributor_id' => 1]);

        $this->assertFalse($user->canManageBranches());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // canApproveOrders / orderRequiresApproval
    // ──────────────────────────────────────────────────────────────────────────

    public function test_no_user_can_approve_orders(): void
    {
        $admin = User::factory()->admin()->make();
        $distributor = User::factory()->make(['distributor_id' => 1]);

        $this->assertFalse($admin->canApproveOrders());
        $this->assertFalse($distributor->canApproveOrders());
    }

    public function test_orders_never_require_approval(): void
    {
        $admin = User::factory()->admin()->make();
        $distributor = User::factory()->make(['distributor_id' => 1]);

        $this->assertFalse($admin->orderRequiresApproval());
        $this->assertFalse($distributor->orderRequiresApproval());
    }
}
