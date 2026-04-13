<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Admin\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy;
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
        $actor = User::factory()->make();
        $target = User::factory()->make();

        $this->assertFalse($this->policy->viewAny($actor));
        $this->assertFalse($this->policy->view($actor, $target));
        $this->assertFalse($this->policy->create($actor));
        $this->assertFalse($this->policy->update($actor, $target));
        $this->assertFalse($this->policy->delete($actor, $target));
    }
}
