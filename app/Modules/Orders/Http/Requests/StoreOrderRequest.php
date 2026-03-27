<?php

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'string', 'email', 'max:120'],
            'company_name' => ['required', 'string', 'max:120'],
            'company_nit' => ['required', 'string', 'max:40', 'regex:/^\d+$/'],
            'company_address' => ['required', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_nit.regex' => 'El NIT/Cédula debe contener solo números.',
        ];
    }
}
