<?php

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departments = config('locations.colombia_departments', []);

        return [
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'string', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'company_name' => ['required', 'string', 'max:120'],
            'company_nit' => ['required', 'string', 'max:40', 'regex:/^\d+$/'],
            'company_address' => ['required', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'department' => ['required', 'string', 'max:120', Rule::in($departments)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'contact_name' => [
                'description' => 'Nombre de la persona de contacto.',
                'example' => 'Ana Gomez',
            ],
            'contact_email' => [
                'description' => 'Correo de contacto para la cotizacion.',
                'example' => 'compras@example.com',
            ],
            'phone' => [
                'description' => 'Telefono de contacto.',
                'example' => '+57 300 123 4567',
            ],
            'company_name' => [
                'description' => 'Razon social o nombre de la empresa solicitante.',
                'example' => 'Distribuciones Demo SAS',
            ],
            'company_nit' => [
                'description' => 'NIT o cedula, solo numeros.',
                'example' => '900123456',
            ],
            'company_address' => [
                'description' => 'Direccion de la empresa.',
                'example' => 'Calle 10 #20-30',
            ],
            'city' => [
                'description' => 'Ciudad de entrega o contacto.',
                'example' => 'Bogota',
            ],
            'department' => [
                'description' => 'Departamento colombiano configurado en el sistema.',
                'example' => 'Cundinamarca',
            ],
            'notes' => [
                'description' => 'Notas opcionales para el pedido.',
                'example' => 'Entregar en horario de oficina.',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_nit.regex' => 'El NIT/Cédula debe contener solo números.',
            'department.in' => 'Selecciona un departamento válido.',
        ];
    }
}
