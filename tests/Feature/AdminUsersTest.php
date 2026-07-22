<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_users_index(): void
    {
        $this->get('/admin/users')
            ->assertRedirect('/login');
    }

    public function test_distributor_cannot_access_admin_users_index(): void
    {
        $distributorUser = User::factory()->create();

        $this->actingAs($distributorUser)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_filter_and_sort_users(): void
    {
        $admin = User::factory()->admin()->create();
        $token = 'ZZ-USERS-FILTER';

        $distributor = Distributor::create([
            'name' => 'Distribuidor Users Filter',
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Usuario '.$token.' Admin',
            'email' => 'admin.'.$token.'@example.com',
            'role' => UserRole::Admin,
            'distributor_id' => null,
        ]);

        $target = User::factory()->create([
            'name' => 'Usuario '.$token.' Dist',
            'email' => 'dist.'.$token.'@example.com',
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
        ]);

        User::factory()->create([
            'name' => 'Usuario sin token',
            'email' => 'sin-token@example.com',
            'role' => UserRole::Distributor,
            'distributor_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/users?role=distributor&distributor_link=linked&q='.$token.'&sort=name_asc&per_page=30');

        $response->assertOk();
        $response->assertSee('Usuario '.$token.' Dist');
        $response->assertDontSee('Usuario '.$token.' Admin');
        $response->assertDontSee('Usuario sin token');

        $response->assertViewHas('users', function ($users) use ($target) {
            if ((int) $users->total() !== 1) {
                return false;
            }

            return $users->getCollection()->first()?->id === $target->id;
        });

        $response->assertViewHas('metrics', fn (array $metrics) => (int) $metrics['total_users'] === 1);
    }

    public function test_admin_can_choose_redirect_after_save_on_store_and_update(): void
    {
        $admin = User::factory()->admin()->create();
        $distributorA = Distributor::create(['name' => 'Distribuidor Redirect A', 'status' => 'active']);
        $distributorB = Distributor::create(['name' => 'Distribuidor Redirect B', 'status' => 'active']);

        $stayResponse = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/admin/users', [
                '_token' => 'test-token',
                'name' => 'Usuario Redirect Stay',
                'email' => 'redirect.stay@example.com',
                'password' => 'password123',
                'role' => 'distributor',
                'distributor_id' => $distributorA->id,
                'after_save' => 'stay',
            ]);

        $created = User::query()->where('email', 'redirect.stay@example.com')->firstOrFail();
        $stayResponse->assertRedirect('/admin/users/'.$created->id.'/edit');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/admin/users', [
                '_token' => 'test-token',
                'name' => 'Usuario Redirect New',
                'email' => 'redirect.new@example.com',
                'password' => 'password123',
                'role' => 'distributor',
                'distributor_id' => $distributorB->id,
                'after_save' => 'new',
            ])
            ->assertRedirect('/admin/users/create');

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/users/'.$created->id, [
                '_token' => 'test-token',
                'name' => 'Usuario Redirect Index',
                'email' => 'redirect.stay@example.com',
                'password' => '',
                'role' => 'distributor',
                'distributor_id' => $distributorA->id,
                'after_save' => 'index',
            ])
            ->assertRedirect('/admin/users');
    }

    public function test_admin_role_ignores_distributor_on_store_and_update(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Distribuidor role',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post('/admin/users', [
                '_token' => 'test-token',
                'name' => 'Usuario Admin Nuevo',
                'email' => 'admin.nuevo@example.com',
                'password' => 'password123',
                'role' => 'admin',
                'distributor_id' => $distributor->id,
            ])
            ->assertRedirect('/admin/users');

        $created = User::query()->where('email', 'admin.nuevo@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $created->id,
            'role' => UserRole::Admin->value,
            'distributor_id' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/users/'.$created->id, [
                '_token' => 'test-token',
                'name' => 'Usuario Admin Edit',
                'email' => 'admin.nuevo@example.com',
                'password' => '',
                'role' => 'admin',
                'distributor_id' => $distributor->id,
            ])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'id' => $created->id,
            'role' => UserRole::Admin->value,
            'distributor_id' => null,
        ]);
    }

    public function test_admin_edit_view_shows_distributor_registration_data(): void
    {
        $admin = User::factory()->admin()->create();

        $distributor = Distributor::create([
            'name' => 'Empresa Verificar SA',
            'status' => 'pending_review',
            'nit' => '900123456',
            'city' => 'Bogotá',
            'address' => 'Calle 100 # 20-30',
            'phone' => '3001234567',
            'contact_email' => 'contacto@empresaverificar.com',
            'contact_name' => 'Carlos Pérez',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users/'.$user->id.'/edit');

        $response->assertOk();
        $response->assertSee('Empresa Verificar SA');
        $response->assertSee('900123456');
        $response->assertSee('Bogotá');
        $response->assertSee('Calle 100 # 20-30');
        $response->assertSee('3001234567');
        $response->assertSee('contacto@empresaverificar.com');
        $response->assertSee('Carlos Pérez');
        $response->assertDontSee('name="distributor_status"', false);
    }

    public function test_admin_users_index_shows_access_accounts_title(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Cuentas de acceso')
            ->assertSee('Nueva cuenta de acceso')
            ->assertSee('Cuentas filtradas')
            ->assertSee('Correo verificado')
            ->assertDontSee('>Usuarios<', false);
    }

    public function test_admin_users_index_shows_expanded_account_details(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Empresa Detalle Usuario',
            'status' => 'active',
        ]);

        $user = User::factory()->unverified()->create([
            'name' => 'Usuario Detalle',
            'email' => 'detalle.usuario@example.com',
            'distributor_id' => $distributor->id,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?q=detalle.usuario@example.com')
            ->assertOk()
            ->assertSee('Correo electrónico')
            ->assertSee('detalle.usuario@example.com')
            ->assertSee('Empresa Detalle Usuario')
            ->assertSee('Ver empresa')
            ->assertSee('Rol y permisos')
            ->assertSee('Acceso al portal distribuidor')
            ->assertSee('Reenviar verificación')
            ->assertSee('Editar cuenta');
    }

    public function test_admin_users_create_and_edit_show_access_account_titles(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Nueva cuenta de acceso');

        $this->actingAs($admin)
            ->get('/admin/users/'.$user->id.'/edit')
            ->assertOk()
            ->assertSee('Editar cuenta de acceso');
    }

    public function test_admin_users_create_prefills_role_and_distributor_from_query_string(): void
    {
        $admin = User::factory()->admin()->create();
        $distributor = Distributor::create([
            'name' => 'Empresa Prefill',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/admin/users/create?role=distributor&distributor_id='.$distributor->id)
            ->assertOk()
            ->assertSee('value="distributor"', false)
            ->assertSee('value="'.$distributor->id.'" selected', false);

        $this->actingAs($admin)
            ->get('/admin/users/create?role=admin&distributor_id='.$distributor->id)
            ->assertOk()
            ->assertSee('value="admin"', false)
            ->assertDontSee('value="'.$distributor->id.'" selected', false);
    }

    public function test_admin_users_create_ignores_invalid_query_string_values(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users/create?role=invalid&distributor_id=999999')
            ->assertOk()
            ->assertSee('value="distributor"', false);
    }

    public function test_sending_distributor_status_on_user_update_does_not_change_distributor_status(): void
    {
        $admin = User::factory()->admin()->create();

        $distributor = Distributor::create([
            'name' => 'Distribuidor No Mutar',
            'status' => 'pending_review',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->put('/admin/users/'.$user->id, [
                '_token' => 'test-token',
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
                'role' => 'distributor',
                'distributor_id' => $distributor->id,
                'distributor_status' => 'active',
                'after_save' => 'index',
            ])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('distributors', [
            'id' => $distributor->id,
            'status' => DistributorStatus::PendingReview->value,
        ]);
    }

    public function test_admin_cannot_delete_himself(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/users/'.$admin->id, ['_token' => 'test-token'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->delete('/admin/users/'.$user->id, ['_token' => 'test-token'])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
