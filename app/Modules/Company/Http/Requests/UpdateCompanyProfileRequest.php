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
            'nit'           => ['required', 'string', 'max:40', 'regex:/^\d+$/'],
            'address'       => ['required', 'string', 'max:180'],
            'city'          => ['required', 'string', 'max:120'],
            'phone'         => ['required', 'string', 'max:40'],
            'contact_email' => ['required', 'string', 'email', 'max:120'],
            'contact_name'  => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'nit.regex' => 'El NIT/Cédula debe contener solo números.',
        ];
    }
}
