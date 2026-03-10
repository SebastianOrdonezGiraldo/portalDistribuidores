<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccessUsersSeeder extends Seeder
{
    public function run(): void
    {
        $demoDistributor = Distributor::firstOrCreate(
            ['name' => 'Distribuidor Demo'],
            ['status' => 'active'],
        );

        User::updateOrCreate(
            ['email' => 'admin@importcorporal.test'],
            [
                'name' => 'Admin Import Corporal',
                'password' => Hash::make('Password123!'),
                'role' => UserRole::Admin,
                'distributor_id' => null,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'dist@importcorporal.test'],
            [
                'name' => 'Usuario Distribuidor Demo',
                'password' => Hash::make('Password123!'),
                'role' => UserRole::Distributor,
                'distributor_id' => $demoDistributor->id,
                'email_verified_at' => now(),
            ],
        );
    }
}
