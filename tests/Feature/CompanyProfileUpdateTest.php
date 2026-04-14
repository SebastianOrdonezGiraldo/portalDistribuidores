<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_distributor_can_view_company_profile(): void
    {
        $distributor = Distributor::factory()->create();

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('empresa.profile.edit'))
            ->assertOk()
            ->assertSee('Datos de Empresa');
    }

    public function test_distributor_can_update_profile_with_complete_master_data(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor Base',
            'status' => 'active',
            'nit' => '123456789',
            'address' => 'Calle 1',
            'city' => 'Bogotá',
            'phone' => '3001234567',
            'contact_email' => 'base@empresa.test',
            'contact_name' => 'Base Contacto',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->from(route('empresa.profile.edit'))
            ->actingAs($user)
            ->put(route('empresa.profile.update'), [
                'name' => 'Distribuidor Actualizado',
                'nit' => '9001234567',
                'address' => 'Calle 10 #20-30',
                'city' => 'Bogotá',
                'phone' => '3001234567',
                'contact_email' => 'compras@empresa.test',
                'contact_name' => 'Laura Gomez',
            ]);

        $response->assertRedirect(route('empresa.profile.edit'));
        $response->assertSessionHas('status', 'Datos de empresa actualizados correctamente.');

        $this->assertDatabaseHas('distributors', [
            'id' => $distributor->id,
            'name' => 'Distribuidor Actualizado',
            'nit' => '9001234567',
            'address' => 'Calle 10 #20-30',
            'city' => 'Bogotá',
            'phone' => '3001234567',
            'contact_email' => 'compras@empresa.test',
            'contact_name' => 'Laura Gomez',
        ]);
    }

    public function test_distributor_can_update_profile_with_numeric_nit(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor Base',
            'status' => 'active',
            'nit' => '123456789',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->from(route('empresa.profile.edit'))
            ->actingAs($user)
            ->put(route('empresa.profile.update'), [
                'name' => 'Distribuidor Actualizado',
                'nit' => '9001234567',
                'address' => 'Calle 10 #20-30',
                'city' => 'Bogotá',
                'phone' => '3001234567',
                'contact_email' => 'compras@empresa.test',
                'contact_name' => 'Laura Gomez',
            ]);

        $response->assertRedirect(route('empresa.profile.edit'));
        $response->assertSessionHas('status', 'Datos de empresa actualizados correctamente.');

        $this->assertDatabaseHas('distributors', [
            'id' => $distributor->id,
            'name' => 'Distribuidor Actualizado',
            'nit' => '9001234567',
            'address' => 'Calle 10 #20-30',
            'city' => 'Bogotá',
            'phone' => '3001234567',
            'contact_email' => 'compras@empresa.test',
            'contact_name' => 'Laura Gomez',
        ]);
    }

    public function test_company_profile_rejects_nit_with_non_numeric_characters(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor Base',
            'status' => 'active',
            'nit' => '123456789',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->from(route('empresa.profile.edit'))
            ->actingAs($user)
            ->put(route('empresa.profile.update'), [
                'name' => 'Distribuidor Base',
                'nit' => '900.123.456-7',
                'address' => 'Calle 10 #20-30',
                'city' => 'Bogotá',
                'phone' => '3001234567',
                'contact_email' => 'compras@empresa.test',
                'contact_name' => 'Laura Gomez',
            ]);

        $response->assertRedirect(route('empresa.profile.edit'));
        $response->assertSessionHasErrors('nit');

        $this->assertDatabaseHas('distributors', [
            'id' => $distributor->id,
            'name' => 'Distribuidor Base',
            'nit' => '123456789',
        ]);
        $this->assertDatabaseMissing('distributors', [
            'id' => $distributor->id,
            'nit' => '900.123.456-7',
        ]);
    }

    public function test_company_profile_requires_all_master_fields(): void
    {
        $distributor = Distributor::create([
            'name' => 'Distribuidor Base',
            'status' => 'active',
            'nit' => '123456789',
            'address' => 'Calle 1',
            'city' => 'Bogotá',
            'phone' => '3001234567',
            'contact_email' => 'base@empresa.test',
            'contact_name' => 'Base Contacto',
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $response = $this->from(route('empresa.profile.edit'))
            ->actingAs($user)
            ->put(route('empresa.profile.update'), []);

        $response->assertRedirect(route('empresa.profile.edit'));
        $response->assertSessionHasErrors([
            'name',
            'nit',
            'address',
            'city',
            'phone',
            'contact_email',
            'contact_name',
        ]);
    }
}
