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
            'name' => ['required', 'string', 'max:120'],
            'nit' => ['required', 'string', 'max:40', 'regex:/^\d+$/'],
            'address' => ['required', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'contact_email' => ['required', 'string', 'email', 'max:120'],
            'contact_name' => ['required', 'string', 'max:120'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Nombre o razon social de la empresa.',
                'example' => 'Distribuciones Demo SAS',
            ],
            'nit' => [
                'description' => 'NIT o cedula, solo numeros.',
                'example' => '900123456',
            ],
            'address' => [
                'description' => 'Direccion principal de la empresa.',
                'example' => 'Calle 10 #20-30',
            ],
            'city' => [
                'description' => 'Ciudad principal de operacion.',
                'example' => 'Bogota',
            ],
            'phone' => [
                'description' => 'Telefono principal de contacto.',
                'example' => '+57 300 123 4567',
            ],
            'contact_email' => [
                'description' => 'Correo del contacto principal.',
                'example' => 'compras@example.com',
            ],
            'contact_name' => [
                'description' => 'Nombre del contacto principal.',
                'example' => 'Ana Gomez',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nit.regex' => 'El NIT/Cédula debe contener solo números.',
        ];
    }
}
