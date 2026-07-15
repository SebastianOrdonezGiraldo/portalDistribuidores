<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StagingSanitizeDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_refuses_to_run_outside_staging_app_env(): void
    {
        config(['app.env' => 'production']);
        config(['database.connections.sqlite.database' => 'portal_distribuidores']);

        $this->artisan('staging:sanitize-data', ['--password' => 'secret123'])
            ->expectsOutputToContain('APP_ENV=staging')
            ->assertExitCode(1);
    }

    public function test_command_refuses_to_run_against_non_staging_database(): void
    {
        config(['app.env' => 'staging']);
        config(['database.connections.sqlite.database' => 'portal_distribuidores']);

        $this->artisan('staging:sanitize-data', ['--password' => 'secret123'])
            ->expectsOutputToContain('portal_distribuidores_staging')
            ->assertExitCode(1);
    }

    public function test_command_sanitizes_staging_data_and_creates_known_access_users(): void
    {
        config(['app.env' => 'staging']);
        config(['database.connections.sqlite.database' => 'portal_distribuidores_staging']);

        $distributor = Distributor::factory()->create([
            'name' => 'Empresa Real SAS',
            'nit' => '900123456-7',
            'address' => 'Calle 100 # 10-20',
            'city' => 'Bogota',
            'phone' => '3001234567',
            'contact_email' => 'ventas@empresa-real.com',
            'contact_name' => 'Contacto Real',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Administradora Real',
            'email' => 'admin@empresa-real.com',
        ]);

        $distributorUser = User::factory()->create([
            'name' => 'Comprador Real',
            'email' => 'compras@empresa-real.com',
            'distributor_id' => $distributor->id,
        ]);

        $otherUser = User::factory()->create([
            'name' => 'Persona Real',
            'email' => 'otro@empresa-real.com',
        ]);

        $order = Order::factory()->forDistributor($distributor)->create([
            'user_id' => $distributorUser->id,
            'contact_name' => 'Contacto Pedido Real',
            'contact_email' => 'pedido@empresa-real.com',
            'company_name' => 'Empresa Pedido Real SAS',
            'company_nit' => '800123456-1',
            'company_address' => 'Carrera 20 # 30-40',
            'city' => 'Medellin',
            'department' => 'Antioquia',
            'phone' => '3101234567',
            'notes' => 'Entregar a una persona identificada.',
            'approval_note' => 'Nota privada de aprobacion.',
            'pdf_path' => 'orders/real.pdf',
        ]);

        $branchId = DB::table('company_branches')->insertGetId([
            'distributor_id' => $distributor->id,
            'name' => 'Sucursal Real',
            'address' => 'Avenida Real 123',
            'city' => 'Cali',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listId = DB::table('company_lists')->insertGetId([
            'distributor_id' => $distributor->id,
            'created_by' => $distributorUser->id,
            'name' => 'Lista confidencial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $historyId = DB::table('order_status_histories')->insertGetId([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => 'submitted',
            'changed_by_user_id' => $admin->id,
            'note' => 'La contacto Ana autorizo el pedido.',
            'metadata' => json_encode(['contact' => 'ana@empresa-real.com']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'admin@empresa-real.com',
            'token' => 'token-real',
            'created_at' => now(),
        ]);

        DB::table('sessions')->insert([
            'id' => 'session-real',
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{"job":"real"}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        DB::table('job_batches')->insert([
            'id' => 'batch-real',
            'name' => 'batch',
            'total_jobs' => 1,
            'pending_jobs' => 1,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'options' => null,
            'cancelled_at' => null,
            'created_at' => now()->timestamp,
            'finished_at' => null,
        ]);

        DB::table('failed_jobs')->insert([
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{"job":"failed"}',
            'exception' => 'boom',
            'failed_at' => now(),
        ]);

        DB::table('cache')->insert([
            'key' => 'key-real',
            'value' => 'cached',
            'expiration' => now()->addHour()->timestamp,
        ]);

        DB::table('cache_locks')->insert([
            'key' => 'lock-real',
            'owner' => 'owner',
            'expiration' => now()->addHour()->timestamp,
        ]);

        DB::table('stock_movements')->insert([
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'source' => 'produccion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contapyme_sync_runs')->insert([
            'id' => '123e4567-e89b-12d3-a456-426614174001',
            'origin' => 'scheduled',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('staging:sanitize-data', ['--password' => 'Secret123!'])
            ->expectsOutputToContain('Sanitizacion de staging completada.')
            ->assertExitCode(0);

        $admin->refresh();
        $distributorUser->refresh();
        $otherUser->refresh();
        $distributor->refresh();
        $order->refresh();

        $this->assertSame('admin-staging@example.test', $admin->email);
        $this->assertSame('Admin Staging', $admin->name);
        $this->assertTrue(Hash::check('Secret123!', $admin->password));

        $this->assertSame('distribuidor-staging@example.test', $distributorUser->email);
        $this->assertSame('Distribuidor Staging', $distributorUser->name);
        $this->assertTrue(Hash::check('Secret123!', $distributorUser->password));

        $this->assertSame("staging+user-{$otherUser->id}@example.test", $otherUser->email);
        $this->assertSame("Usuario Staging {$otherUser->id}", $otherUser->name);
        $this->assertFalse(Hash::check('Secret123!', $otherUser->password));

        $this->assertSame("Distribuidor Staging {$distributor->id}", $distributor->name);
        $this->assertSame('900'.str_pad((string) $distributor->id, 6, '0', STR_PAD_LEFT), $distributor->nit);
        $this->assertSame("Direccion Staging {$distributor->id}", $distributor->address);
        $this->assertSame('Ciudad Staging', $distributor->city);
        $this->assertSame('0000000000', $distributor->phone);
        $this->assertSame("staging+distributor-{$distributor->id}@example.test", $distributor->contact_email);
        $this->assertSame("Contacto Staging {$distributor->id}", $distributor->contact_name);

        $this->assertSame("Contacto Staging {$order->id}", $order->contact_name);
        $this->assertSame("staging+order-{$order->id}@example.test", $order->contact_email);
        $this->assertSame("Empresa Staging {$order->id}", $order->company_name);
        $this->assertSame('800'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), $order->company_nit);
        $this->assertSame("Direccion Staging {$order->id}", $order->company_address);
        $this->assertSame('Ciudad Staging', $order->city);
        $this->assertSame('Departamento Staging', $order->department);
        $this->assertSame('0000000000', $order->phone);
        $this->assertNull($order->notes);
        $this->assertNull($order->approval_note);
        $this->assertNull($order->pdf_path);

        $this->assertDatabaseHas('company_branches', [
            'id' => $branchId,
            'name' => "Sucursal Staging {$branchId}",
            'address' => "Direccion Staging {$branchId}",
            'city' => 'Ciudad Staging',
        ]);
        $this->assertDatabaseHas('company_lists', [
            'id' => $listId,
            'name' => "Lista Staging {$listId}",
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'id' => $historyId,
            'note' => null,
            'metadata' => null,
        ]);

        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertDatabaseCount('sessions', 0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('job_batches', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertDatabaseCount('cache', 0);
        $this->assertDatabaseCount('cache_locks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('contapyme_sync_runs', 0);
    }
}
