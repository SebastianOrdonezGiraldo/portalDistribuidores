<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Admin\Policies\DistributorPolicy;
use App\Modules\AuthAccess\Models\Distributor;
use Tests\TestCase;

class DistributorPolicyTest extends TestCase
{
    private DistributorPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new DistributorPolicy;
    }

    public function test_before_grants_everything_to_admin(): void
    {
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->before($admin));
    }

    public function test_before_returns_null_for_non_admin(): void
    {
        $distributor = User::factory()->make();

        $this->assertNull($this->policy->before($distributor));
    }

    public function test_all_explicit_abilities_return_false_for_non_admin_users(): void
    {
        $distributorUser = User::factory()->make();
        $distributor = Distributor::factory()->make();

        $this->assertFalse($this->policy->viewAny($distributorUser));
        $this->assertFalse($this->policy->view($distributorUser, $distributor));
        $this->assertFalse($this->policy->create($distributorUser));
        $this->assertFalse($this->policy->update($distributorUser, $distributor));
        $this->assertFalse($this->policy->updateTier($distributorUser, $distributor));
        $this->assertFalse($this->policy->delete($distributorUser, $distributor));
    }
}
