<?php

namespace App\Modules\Admin\Http\Requests;

use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'distributor_id' => ['nullable', 'integer', 'exists:distributors,id', 'required_if:role,'.UserRole::Distributor->value],
        ];
    }
}

