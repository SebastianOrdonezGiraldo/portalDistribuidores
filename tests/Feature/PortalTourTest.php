<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_tour_starts_automatically_for_a_distributor_company_that_has_not_completed_it(): void
    {
        $user = $this->distributorUser();

        $this->actingAs($user)
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('data-portal-tour-root', false)
            ->assertSee('data-auto-start="true"', false)
            ->assertSee('data-portal-tour-restart', false);
    }

    public function test_completing_the_tour_is_persisted_for_the_company(): void
    {
        $user = $this->distributorUser();

        $this->actingAs($user)
            ->postJson(route('empresa.portal-tour.complete'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($user->distributor()->firstOrFail()->portal_tour_completed_at);

        $this->actingAs($user)
            ->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('data-auto-start="false"', false)
            ->assertSee('data-portal-tour-restart', false);
    }

    public function test_completed_tour_can_be_requested_again_without_clearing_company_state(): void
    {
        $user = $this->distributorUser(now());

        $this->actingAs($user)
            ->get(route('catalog.index', ['tutorial' => 1]))
            ->assertOk()
            ->assertSee('data-auto-start="true"', false)
            ->assertSee('data-replay-requested="true"', false);

        $this->assertNotNull($user->distributor()->firstOrFail()->portal_tour_completed_at);
    }

    private function distributorUser(mixed $tourCompletedAt = null): User
    {
        $distributor = Distributor::factory()->create([
            'portal_tour_completed_at' => $tourCompletedAt,
        ]);

        return User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);
    }
}
