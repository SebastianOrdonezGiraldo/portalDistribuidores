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
            'contact_email' => 'ventas@empresa-real.com',
        ]);

        $admin = User::factory()->admin()->create([
            'email' => 'admin@empresa-real.com',
        ]);

        $distributorUser = User::factory()->create([
            'email' => 'compras@empresa-real.com',
            'distributor_id' => $distributor->id,
        ]);

        $otherUser = User::factory()->create([
            'email' => 'otro@empresa-real.com',
        ]);

        $order = Order::factory()->forDistributor($distributor)->create([
            'user_id' => $distributorUser->id,
            'contact_email' => 'pedido@empresa-real.com',
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

        $this->artisan('staging:sanitize-data', ['--password' => 'Secret123!'])
            ->expectsOutputToContain('Sanitizacion de staging completada.')
            ->assertExitCode(0);

        $admin->refresh();
        $distributorUser->refresh();
        $otherUser->refresh();
        $distributor->refresh();
        $order->refresh();

        $this->assertSame('admin-staging@example.test', $admin->email);
        $this->assertTrue(Hash::check('Secret123!', $admin->password));

        $this->assertSame('distribuidor-staging@example.test', $distributorUser->email);
        $this->assertTrue(Hash::check('Secret123!', $distributorUser->password));

        $this->assertSame("staging+user-{$otherUser->id}@example.test", $otherUser->email);
        $this->assertFalse(Hash::check('Secret123!', $otherUser->password));

        $this->assertSame("staging+distributor-{$distributor->id}@example.test", $distributor->contact_email);
        $this->assertSame("staging+order-{$order->id}@example.test", $order->contact_email);

        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertDatabaseCount('sessions', 0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('job_batches', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
        $this->assertDatabaseCount('cache', 0);
        $this->assertDatabaseCount('cache_locks', 0);
    }
}
