<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Policies\CategoryPolicy;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    private CategoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CategoryPolicy;
    }

    public function test_view_any_and_view_allow_admin_and_distributor(): void
    {
        $admin = User::factory()->admin()->make();
        $distributor = User::factory()->make();
        $category = Category::factory()->make();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($distributor));
        $this->assertTrue($this->policy->view($admin, $category));
        $this->assertTrue($this->policy->view($distributor, $category));
    }

    public function test_create_update_and_delete_are_admin_only(): void
    {
        $admin = User::factory()->admin()->make();
        $distributor = User::factory()->make();
        $category = Category::factory()->make();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($distributor));

        $this->assertTrue($this->policy->update($admin, $category));
        $this->assertFalse($this->policy->update($distributor, $category));

        $this->assertTrue($this->policy->delete($admin, $category));
        $this->assertFalse($this->policy->delete($distributor, $category));
    }
}
