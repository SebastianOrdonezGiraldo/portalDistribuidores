<?php

namespace App\Modules\Admin\Http\Requests;

use App\Models\User;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id', 'required_if:role,'.UserRole::Distributor->value, Rule::unique('users', 'distributor_id')->ignore($user?->id)],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Nombre del usuario.',
                'example' => 'Ana Gomez',
            ],
            'email' => [
                'description' => 'Correo unico para iniciar sesion.',
                'example' => 'ana@example.com',
            ],
            'password' => [
                'description' => 'Nueva contrasena. Se omite para conservar la actual.',
                'example' => 'secret-password',
            ],
            'role' => [
                'description' => 'Rol asignado al usuario.',
                'example' => UserRole::Distributor->value,
            ],
            'distributor_id' => [
                'description' => 'ID del distribuidor asociado cuando el rol es distribuidor.',
                'example' => 7,
            ],
        ];
    }
}
