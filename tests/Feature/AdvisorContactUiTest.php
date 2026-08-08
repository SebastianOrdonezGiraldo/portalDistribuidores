<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\CommerceTierAdvisor;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvisorContactUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gold_distributor_uses_gold_advisor_whatsapp(): void
    {
        $user = $this->userForTier(DistributorTier::Gold);
        $this->createAdvisor(DistributorTier::Gold, 'Oro UI', 'oro-ui@example.test', '573111111111');

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk()
            ->assertSee('https://wa.me/573111111111', false)
            ->assertSee('href="https://wa.me/573111111111?text=', false)
            ->assertDontSee('https://wa.me/573222222222', false);
    }

    public function test_silver_distributor_uses_silver_advisor_whatsapp(): void
    {
        $user = $this->userForTier(DistributorTier::Silver);
        $this->createAdvisor(DistributorTier::Silver, 'Plata UI', 'plata-ui@example.test', '573222222222');

        $response = $this->actingAs($user)->get(route('catalog.index'));

        $response->assertOk()
            ->assertSee('https://wa.me/573222222222', false)
            ->assertSee('href="https://wa.me/573222222222?text=', false)
            ->assertDontSee('https://wa.me/573111111111', false);
    }

    public function test_guest_uses_general_support_whatsapp(): void
    {
        config(['commerce.support.whatsapp_number' => '573333333333']);
        $this->createAdvisor(DistributorTier::Gold, 'Oro UI', 'oro-ui@example.test', '573111111111');

        $response = $this->get(route('catalog.index'));

        $response->assertOk()
            ->assertSee('https://wa.me/573333333333', false)
            ->assertSee('href="https://wa.me/573333333333?text=', false)
            ->assertDontSee('https://wa.me/573111111111', false);
    }

    public function test_missing_support_number_does_not_render_internal_fallback_link(): void
    {
        $floatingBubble = view('layouts.partials.whatsapp-float', [
            'currentAdvisor' => null,
            'supportWhatsappUrl' => null,
        ])->render();

        $this->assertStringNotContainsString('catalog-support-bubble--whatsapp', $floatingBubble);
    }

    private function userForTier(DistributorTier $tier): User
    {
        $distributor = Distributor::factory()->create([
            'tier' => $tier,
            'status' => 'active',
        ]);

        return User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);
    }

    private function createAdvisor(DistributorTier $tier, string $name, string $email, string $whatsapp): void
    {
        CommerceTierAdvisor::query()->create([
            'tier' => $tier,
            'advisor_name' => $name,
            'advisor_email' => $email,
            'advisor_whatsapp' => $whatsapp,
        ]);
    }
}
