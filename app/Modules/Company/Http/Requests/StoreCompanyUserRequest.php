<?php

namespace App\Modules\Company\Http\Requests;

use App\Modules\Shared\Enums\CompanyRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:120'],
            'email'        => ['required', 'email', 'max:160', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'max:255'],
            'company_role' => ['required', Rule::enum(CompanyRole::class)],
        ];
    }
}
