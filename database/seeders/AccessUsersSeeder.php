<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AccessUsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = $this->requiredConfigString(
            'seeders.access_users.admin.email',
            'ACCESS_USERS_ADMIN_EMAIL',
        );
        $adminPassword = $this->requiredConfigString(
            'seeders.access_users.admin.password',
            'ACCESS_USERS_ADMIN_PASSWORD',
        );

        $distributorEmail = $this->requiredConfigString(
            'seeders.access_users.distributor.email',
            'ACCESS_USERS_DISTRIBUTOR_EMAIL',
        );
        $distributorPassword = $this->requiredConfigString(
            'seeders.access_users.distributor.password',
            'ACCESS_USERS_DISTRIBUTOR_PASSWORD',
        );

        $demoDistributor = Distributor::firstOrCreate(
            ['name' => 'Distribuidor Demo'],
            ['status' => 'active'],
        );

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => (string) config('seeders.access_users.admin.name', 'Admin Import Corporal'),
                'password' => Hash::make($adminPassword),
                'role' => UserRole::Admin,
                'distributor_id' => null,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => $distributorEmail],
            [
                'name' => (string) config('seeders.access_users.distributor.name', 'Usuario Distribuidor Demo'),
                'password' => Hash::make($distributorPassword),
                'role' => UserRole::Distributor,
                'distributor_id' => $demoDistributor->id,
                'email_verified_at' => now(),
            ],
        );
    }

    private function requiredConfigString(string $configKey, string $envKey): string
    {
        $value = trim((string) config($configKey, ''));

        if ($value !== '') {
            return $value;
        }

        throw new RuntimeException(
            "Falta la variable {$envKey} en .env para ejecutar AccessUsersSeeder.",
        );
    }
}
