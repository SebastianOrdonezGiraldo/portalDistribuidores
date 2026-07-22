<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDistributorTierTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_tier_from_silver_to_gold(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();

        $this->actingAs($admin)
            ->from('/admin/distributors')
            ->patch(route('admin.distributors.tier.update', $distributor), ['tier' => 'oro'])
            ->assertRedirect('/admin/distributors')
            ->assertSessionHas('status');

        $distributor->refresh();
        $this->assertSame(DistributorTier::Gold, $distributor->tier);
        $this->assertSame($admin->id, $distributor->tier_changed_by_id);
        $this->assertNotNull($distributor->tier_changed_at);
    }

    public function test_admin_can_change_tier_from_gold_to_silver(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->gold()->create();

        $this->actingAs($admin)
            ->patch(route('admin.distributors.tier.update', $distributor), ['tier' => 'plata'])
            ->assertRedirect();

        $this->assertSame(DistributorTier::Silver, $distributor->refresh()->tier);
    }

    public function test_distributor_cannot_change_own_tier(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $distributorUser = User::factory()->create(['distributor_id' => $distributor->id]);

        $this->actingAs($distributorUser)
            ->patch(route('admin.distributors.tier.update', $distributor), ['tier' => 'oro'])
            ->assertForbidden();

        $this->assertSame(DistributorTier::Silver, $distributor->refresh()->tier);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $distributor = Distributor::factory()->silver()->create();

        $this->patch(route('admin.distributors.tier.update', $distributor), ['tier' => 'oro'])
            ->assertRedirect('/login');
    }

    public function test_invalid_tier_returns_validation_error_on_web_route(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();

        $this->actingAs($admin)
            ->from('/admin/distributors')
            ->patch(route('admin.distributors.tier.update', $distributor), ['tier' => 'diamante'])
            ->assertRedirect('/admin/distributors')
            ->assertSessionHasErrors('tier');

        $this->assertSame(DistributorTier::Silver, $distributor->refresh()->tier);
    }

    public function test_same_tier_does_not_record_a_change(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::factory()->silver()->create();

        $this->actingAs($admin)
            ->patch(route('admin.distributors.tier.update', $distributor), ['tier' => 'plata'])
            ->assertRedirect();

        $distributor->refresh();
        $this->assertSame(DistributorTier::Silver, $distributor->tier);
        $this->assertNull($distributor->tier_changed_at);
        $this->assertNull($distributor->tier_changed_by_id);
    }
}
