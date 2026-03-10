<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminDistributorsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected_from_admin_distributors_index(): void
    {
        $this->get('/admin/distributors')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_distributors_index(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/distributors')
            ->assertForbidden();
    }

    public function test_admin_can_filter_and_sort_distributors(): void
    {
        $admin = User::factory()->admin()->create();
        $token = 'ZZ-DIST-FILTER';

        $target = Distributor::create([
            'name' => 'Distribuidor '.$token.' Principal',
            'status' => 'active',
        ]);

        Distributor::create([
            'name' => 'Distribuidor '.$token.' Inactivo',
            'status' => 'inactive',
        ]);

        Distributor::create([
            'name' => 'Distribuidor sin token',
            'status' => 'active',
        ]);

        User::factory()->create([
            'distributor_id' => $target->id,
        ]);

        $this->createOrder($admin, $target, 'CTC-DIST-001');

        $response = $this->actingAs($admin)
            ->get('/admin/distributors?status=active&relation=with_users&q='.$token.'&sort=users_desc&per_page=30');

        $response->assertOk();
        $response->assertSee('Distribuidor '.$token.' Principal');
        $response->assertDontSee('Distribuidor '.$token.' Inactivo');
        $response->assertDontSee('Distribuidor sin token');

        $response->assertViewHas('distributors', function ($distributors) use ($target) {
            if ((int) $distributors->total() !== 1) {
                return false;
            }

            return $distributors->getCollection()->first()?->id === $target->id;
        });

        $response->assertViewHas('metrics', fn (array $metrics) => (int) $metrics['total_distributors'] === 1);
    }

    public function test_admin_cannot_delete_distributor_with_users(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor con usuarios',
            'status' => 'active',
        ]);

        User::factory()->create([
            'distributor_id' => $distributor->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/distributors/'.$distributor->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('distributors', ['id' => $distributor->id]);
    }

    public function test_admin_cannot_delete_distributor_with_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor con pedidos',
            'status' => 'active',
        ]);

        $this->createOrder($admin, $distributor, 'CTC-DIST-DELETE-001');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/distributors/'.$distributor->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('distributors', ['id' => $distributor->id]);
    }

    public function test_admin_can_toggle_distributor_status(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor estado',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->patch('/admin/distributors/'.$distributor->id.'/status', [
                '_token' => 'test-token',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('distributors', [
            'id' => $distributor->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_choose_redirect_after_save_on_store_and_update(): void
    {
        $admin = User::factory()->admin()->create();

        $stayResponse = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/admin/distributors', [
                '_token' => 'test-token',
                'name' => 'Distribuidor redirect stay',
                'status' => 'active',
                'after_save' => 'stay',
            ]);

        $created = Distributor::query()->where('name', 'Distribuidor redirect stay')->firstOrFail();
        $stayResponse->assertRedirect('/admin/distributors/'.$created->id.'/edit');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/admin/distributors', [
                '_token' => 'test-token',
                'name' => 'Distribuidor redirect new',
                'status' => 'active',
                'after_save' => 'new',
            ])
            ->assertRedirect('/admin/distributors/create');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/distributors/'.$created->id, [
                '_token' => 'test-token',
                'name' => 'Distribuidor redirect index',
                'status' => 'active',
                'after_save' => 'index',
            ])
            ->assertRedirect('/admin/distributors');
    }

    private function createOrder(User $admin, Distributor $distributor, string $ocNumber): Order
    {
        return Order::create([
            'distributor_id' => $distributor->id,
            'user_id' => $admin->id,
            'oc_number' => $ocNumber,
            'contact_name' => 'Contacto Test',
            'contact_email' => 'contacto+'.$ocNumber.'@example.com',
            'company_name' => 'Empresa Test',
            'company_nit' => '900111222',
            'company_address' => 'Calle 10 # 20-30',
            'city' => 'Bogota',
            'phone' => '3000000000',
            'notes' => 'Orden de prueba',
            'status' => OrderStatus::Submitted,
            'total_amount' => 120000,
            'pdf_path' => null,
        ]);
    }
}
