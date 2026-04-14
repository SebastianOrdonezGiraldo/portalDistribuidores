<?php

namespace App\Modules\Admin\Http\Requests;

use App\Models\User;
use App\Modules\Shared\Enums\DistributorStatus;
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
            'distributor_status' => ['nullable', Rule::enum(DistributorStatus::class)],
        ];
    }
}
