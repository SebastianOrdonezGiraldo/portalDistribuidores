<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_can_login(): void
    {
        $password = Str::random(32);

        $distributor = Distributor::create([
            'name' => 'Distribuidor Test',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Distribuidor',
            'email' => 'dist@login.test',
            'password' => Hash::make($password),
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'dist@login.test',
            'password' => $password,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
