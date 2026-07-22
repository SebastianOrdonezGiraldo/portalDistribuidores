<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Pricing\DistributorTierResolver;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistributorTierResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): DistributorTierResolver
    {
        return new DistributorTierResolver;
    }

    public function test_guest_resolves_to_silver(): void
    {
        $this->assertSame(DistributorTier::Silver, $this->resolver()->resolve(null));
    }

    public function test_admin_without_company_resolves_to_silver(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame(DistributorTier::Silver, $this->resolver()->resolve($admin));
    }

    public function test_silver_distributor_resolves_to_silver(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);

        $this->assertSame(DistributorTier::Silver, $this->resolver()->resolve($user));
    }

    public function test_gold_distributor_resolves_to_gold(): void
    {
        $distributor = Distributor::factory()->gold()->create();
        $user = User::factory()->create(['distributor_id' => $distributor->id]);

        $this->assertSame(DistributorTier::Gold, $this->resolver()->resolve($user));
    }
}
