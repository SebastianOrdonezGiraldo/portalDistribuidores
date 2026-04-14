<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class SanitizeStagingData extends Command
{
    private const EXPECTED_APP_ENV = 'staging';

    private const EXPECTED_DATABASE = 'portal_distribuidores_staging';

    private const DEFAULT_ADMIN_EMAIL = 'admin-staging@example.test';

    private const DEFAULT_DISTRIBUTOR_EMAIL = 'distribuidor-staging@example.test';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'staging:sanitize-data
        {--password= : Password to assign to the staging access users}
        {--admin-email= : Login email for the staging admin user}
        {--distributor-email= : Login email for the staging distributor user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sanitize restored production data inside the staging database only.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->guardEnvironment();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $password = trim((string) ($this->option('password') ?: env('STAGING_SANITIZE_PASSWORD', '')));
        $adminEmail = trim((string) ($this->option('admin-email') ?: self::DEFAULT_ADMIN_EMAIL));
        $distributorEmail = trim((string) ($this->option('distributor-email') ?: self::DEFAULT_DISTRIBUTOR_EMAIL));

        if ($password === '') {
            $this->components->error('Define STAGING_SANITIZE_PASSWORD o usa --password para fijar las credenciales de acceso de staging.');

            return self::FAILURE;
        }

        $knownPasswordHash = Hash::make($password);
        $disabledPasswordHash = Hash::make(Str::random(64));

        DB::transaction(function () use ($knownPasswordHash, $disabledPasswordHash, $adminEmail, $distributorEmail): void {
            $this->clearOperationalTables();
            $this->anonymizeDistributorEmails();
            $this->anonymizeOrderEmails();
            $this->lockDownUsers($disabledPasswordHash);
            $this->promoteAccessUsers($knownPasswordHash, $adminEmail, $distributorEmail);
        }, 3);

        $this->components->info('Sanitizacion de staging completada.');
        $this->line("Admin staging: {$adminEmail}");
        $this->line("Distribuidor staging: {$distributorEmail}");

        return self::SUCCESS;
    }

    private function guardEnvironment(): void
    {
        $appEnv = (string) config('app.env');
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($appEnv !== self::EXPECTED_APP_ENV) {
            throw new RuntimeException('Este comando solo puede ejecutarse con APP_ENV='.self::EXPECTED_APP_ENV.". Entorno actual: {$appEnv}.");
        }

        if ($database !== self::EXPECTED_DATABASE) {
            throw new RuntimeException('Este comando solo puede ejecutarse sobre la base '.self::EXPECTED_DATABASE.". Base actual: {$database}.");
        }
    }

    private function clearOperationalTables(): void
    {
        foreach (['password_reset_tokens', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function anonymizeDistributorEmails(): void
    {
        Distributor::query()
            ->select(['id'])
            ->whereNotNull('contact_email')
            ->orderBy('id')
            ->chunkById(100, function ($distributors): void {
                foreach ($distributors as $distributor) {
                    DB::table('distributors')
                        ->where('id', $distributor->id)
                        ->update([
                            'contact_email' => "staging+distributor-{$distributor->id}@example.test",
                        ]);
                }
            });
    }

    private function anonymizeOrderEmails(): void
    {
        Order::query()
            ->select(['id'])
            ->whereNotNull('contact_email')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'contact_email' => "staging+order-{$order->id}@example.test",
                        ]);
                }
            });
    }

    private function lockDownUsers(string $disabledPasswordHash): void
    {
        DB::table('users')->update([
            'password' => $disabledPasswordHash,
            'remember_token' => null,
        ]);

        User::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'email' => "staging+user-{$user->id}@example.test",
                        ]);
                }
            });
    }

    private function promoteAccessUsers(string $knownPasswordHash, string $adminEmail, string $distributorEmail): void
    {
        $admin = User::query()
            ->where('role', UserRole::Admin->value)
            ->orderBy('id')
            ->first();

        if ($admin) {
            DB::table('users')
                ->where('id', $admin->id)
                ->update([
                    'email' => $adminEmail,
                    'password' => $knownPasswordHash,
                    'remember_token' => null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Admin Staging',
                'email' => $adminEmail,
                'role' => UserRole::Admin->value,
                'distributor_id' => null,
                'email_verified_at' => now(),
                'password' => $knownPasswordHash,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $distributorUser = User::query()
            ->where('role', UserRole::Distributor->value)
            ->orderBy('id')
            ->first();

        $distributorId = Distributor::query()->orderBy('id')->value('id');

        if ($distributorUser) {
            DB::table('users')
                ->where('id', $distributorUser->id)
                ->update([
                    'email' => $distributorEmail,
                    'password' => $knownPasswordHash,
                    'remember_token' => null,
                    'distributor_id' => $distributorUser->distributor_id ?? $distributorId,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('users')->insert([
                'name' => 'Distribuidor Staging',
                'email' => $distributorEmail,
                'role' => UserRole::Distributor->value,
                'distributor_id' => $distributorId,
                'email_verified_at' => now(),
                'password' => $knownPasswordHash,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
