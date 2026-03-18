<?php

namespace App\Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:120'],
            'nit'           => ['nullable', 'string', 'max:40'],
            'address'       => ['nullable', 'string', 'max:180'],
            'city'          => ['nullable', 'string', 'max:120'],
            'phone'         => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'contact_name'  => ['nullable', 'string', 'max:120'],
        ];
    }
}
