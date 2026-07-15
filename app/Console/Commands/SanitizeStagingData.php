<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
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
            $this->anonymizeDistributors();
            $this->anonymizeCompanyBranches();
            $this->anonymizeOrders();
            $this->anonymizeOrderStatusHistories();
            $this->anonymizeCompanyLists();
            $this->lockDownUsers($disabledPasswordHash);
            $this->promoteAccessUsers($knownPasswordHash, $adminEmail, $distributorEmail);
            $this->verifySanitizedEmails($adminEmail, $distributorEmail);
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
        foreach ([
            'password_reset_tokens',
            'sessions',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
            'document_downloads',
            'stock_movements',
            'contapyme_sync_runs',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function anonymizeDistributors(): void
    {
        Distributor::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($distributors): void {
                foreach ($distributors as $distributor) {
                    DB::table('distributors')
                        ->where('id', $distributor->id)
                        ->update([
                            'name' => "Distribuidor Staging {$distributor->id}",
                            'nit' => '900'.str_pad((string) $distributor->id, 6, '0', STR_PAD_LEFT),
                            'address' => "Direccion Staging {$distributor->id}",
                            'city' => 'Ciudad Staging',
                            'phone' => '0000000000',
                            'contact_email' => "staging+distributor-{$distributor->id}@example.test",
                            'contact_name' => "Contacto Staging {$distributor->id}",
                        ]);
                }
            });
    }

    private function anonymizeCompanyBranches(): void
    {
        if (! Schema::hasTable('company_branches')) {
            return;
        }

        DB::table('company_branches')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($branches): void {
                foreach ($branches as $branch) {
                    DB::table('company_branches')
                        ->where('id', $branch->id)
                        ->update([
                            'name' => "Sucursal Staging {$branch->id}",
                            'address' => "Direccion Staging {$branch->id}",
                            'city' => 'Ciudad Staging',
                        ]);
                }
            });
    }

    private function anonymizeOrders(): void
    {
        DB::table('orders')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'contact_name' => "Contacto Staging {$order->id}",
                            'contact_email' => "staging+order-{$order->id}@example.test",
                            'company_name' => "Empresa Staging {$order->id}",
                            'company_nit' => '800'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                            'company_address' => "Direccion Staging {$order->id}",
                            'city' => 'Ciudad Staging',
                            'department' => 'Departamento Staging',
                            'phone' => '0000000000',
                            'notes' => null,
                            'approval_note' => null,
                            'pdf_path' => null,
                        ]);
                }
            });
    }

    private function anonymizeOrderStatusHistories(): void
    {
        if (! Schema::hasTable('order_status_histories')) {
            return;
        }

        DB::table('order_status_histories')->update([
            'note' => null,
            'metadata' => null,
        ]);
    }

    private function anonymizeCompanyLists(): void
    {
        if (! Schema::hasTable('company_lists')) {
            return;
        }

        DB::table('company_lists')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($lists): void {
                foreach ($lists as $list) {
                    DB::table('company_lists')
                        ->where('id', $list->id)
                        ->update([
                            'name' => "Lista Staging {$list->id}",
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
                            'name' => "Usuario Staging {$user->id}",
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
                    'name' => 'Admin Staging',
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
                    'name' => 'Distribuidor Staging',
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

    private function verifySanitizedEmails(string $adminEmail, string $distributorEmail): void
    {
        $unsafeUsers = DB::table('users')
            ->whereNotIn('email', [$adminEmail, $distributorEmail])
            ->where('email', 'not like', '%@example.test')
            ->count();
        $unsafeDistributors = DB::table('distributors')
            ->whereNotNull('contact_email')
            ->where('contact_email', 'not like', '%@example.test')
            ->count();
        $unsafeOrders = DB::table('orders')
            ->whereNotNull('contact_email')
            ->where('contact_email', 'not like', '%@example.test')
            ->count();

        if ($unsafeUsers + $unsafeDistributors + $unsafeOrders > 0) {
            throw new RuntimeException('La sanitizacion dejo emails externos en la base de staging.');
        }
    }
}
