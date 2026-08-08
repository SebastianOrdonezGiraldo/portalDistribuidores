<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\CommercePricingRule;
use App\Modules\Shared\Enums\DistributorTier;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceTierAdvisorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_and_update_commercial_advisors_without_versioning_pricing(): void
    {
        $admin = User::factory()->admin()->create();
        $pricingCount = CommercePricingRule::query()->count();

        $this->actingAs($admin)
            ->get(route('admin.settings.commerce.edit'))
            ->assertOk()
            ->assertSee('Asesores comerciales')
            ->assertSee('Guardar asesores');

        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($admin)
            ->patch(route('admin.settings.commerce.advisors.update'), $this->payload());

        $response
            ->assertRedirect(route('admin.settings.commerce.edit'))
            ->assertSessionHas('status', 'Asesores comerciales actualizados correctamente.');

        $this->assertDatabaseHas('commerce_tier_advisors', [
            'tier' => DistributorTier::Gold->value,
            'advisor_name' => 'Carolina Oro',
            'advisor_email' => 'carolina.oro@example.test',
            'advisor_whatsapp' => '573117479607',
        ]);
        $this->assertDatabaseHas('commerce_tier_advisors', [
            'tier' => DistributorTier::Silver->value,
            'advisor_name' => 'Laura Plata',
            'advisor_email' => 'laura.plata@example.test',
            'advisor_whatsapp' => '573001112233',
        ]);
        $this->assertSame($pricingCount, CommercePricingRule::query()->count());
    }

    public function test_non_admin_cannot_update_advisors(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user)
            ->patch(route('admin.settings.commerce.advisors.update'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('commerce_tier_advisors', 0);
    }

    public function test_invalid_email_and_whatsapp_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->payload();
        $payload['advisors']['oro']['advisor_email'] = 'not-an-email';
        $payload['advisors']['plata']['advisor_whatsapp'] = 'not a phone';

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->from(route('admin.settings.commerce.edit'))
            ->actingAs($admin)
            ->patch(route('admin.settings.commerce.advisors.update'), $payload)
            ->assertRedirect(route('admin.settings.commerce.edit'))
            ->assertSessionHasErrors([
                'advisors.oro.advisor_email',
                'advisors.plata.advisor_whatsapp',
            ]);

        $this->assertDatabaseCount('commerce_tier_advisors', 0);
    }

    public function test_saving_again_updates_two_rows_instead_of_creating_duplicates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($admin)
            ->patch(route('admin.settings.commerce.advisors.update'), $this->payload())
            ->assertRedirect();

        $payload = $this->payload();
        $payload['advisors']['oro']['advisor_name'] = 'Carolina Actualizada';

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($admin)
            ->patch(route('admin.settings.commerce.advisors.update'), $payload)
            ->assertRedirect();

        $this->assertDatabaseCount('commerce_tier_advisors', 2);
        $this->assertDatabaseHas('commerce_tier_advisors', [
            'tier' => DistributorTier::Gold->value,
            'advisor_name' => 'Carolina Actualizada',
        ]);
    }

    /**
     * @return array{advisors: array<string, array<string, string>>}
     */
    private function payload(): array
    {
        return [
            'advisors' => [
                DistributorTier::Gold->value => [
                    'advisor_name' => 'Carolina Oro',
                    'advisor_email' => 'carolina.oro@example.test',
                    'advisor_whatsapp' => '+57 311 747 9607',
                ],
                DistributorTier::Silver->value => [
                    'advisor_name' => 'Laura Plata',
                    'advisor_email' => 'laura.plata@example.test',
                    'advisor_whatsapp' => '300 111 2233',
                ],
            ],
        ];
    }
}
