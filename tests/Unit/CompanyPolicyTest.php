<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Company\Policies\CompanyPolicy;
use App\Modules\Shared\Enums\CompanyRole;
use Tests\TestCase;

class CompanyPolicyTest extends TestCase
{
    private CompanyPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new CompanyPolicy;
    }

    public function test_edit_company_allows_only_active_distributor_with_distributor_id(): void
    {
        $activeDistributor = User::factory()->make([
            'distributor_id' => 10,
            'company_role' => CompanyRole::AdminEmpresa,
            'is_active' => true,
        ]);
        $inactiveDistributor = User::factory()->inactive()->make([
            'distributor_id' => 10,
            'company_role' => CompanyRole::AdminEmpresa,
        ]);
        $distributorWithoutCompany = User::factory()->make([
            'distributor_id' => null,
            'company_role' => CompanyRole::AdminEmpresa,
        ]);
        $admin = User::factory()->admin()->make();

        $this->assertTrue($this->policy->editCompany($activeDistributor));
        $this->assertFalse($this->policy->editCompany($inactiveDistributor));
        $this->assertFalse($this->policy->editCompany($distributorWithoutCompany));
        $this->assertFalse($this->policy->editCompany($admin));
    }
}
