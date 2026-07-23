<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Shared\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('checkout_intent')) {
            $this->merge(['checkout_intent' => 'quote']);
        }
    }

    public function rules(): array
    {
        $departments = config('locations.colombia_departments', []);
        $intent = (string) $this->input('checkout_intent', 'quote');

        $rules = [
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'string', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'company_name' => ['required', 'string', 'max:120'],
            'company_nit' => ['required', 'string', 'max:40', 'regex:/^\d+$/'],
            'company_address' => ['required', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'department' => ['required', 'string', 'max:120', Rule::in($departments)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'checkout_intent' => ['required', 'string', Rule::in(['quote', 'pay'])],
            'payment_method' => [
                Rule::requiredIf(fn () => $intent === 'pay'),
                'nullable',
                'string',
                Rule::in(PaymentMethod::values()),
            ],
        ];

        return $rules;
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
            'checkout_intent' => [
                'description' => 'quote = solo cotizar; pay = pagar ahora.',
                'example' => 'quote',
            ],
            'payment_method' => [
                'description' => 'Método de pago manual (requerido si checkout_intent=pay).',
                'example' => 'bancolombia',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_nit.regex' => 'El NIT/Cédula debe contener solo números.',
            'department.in' => 'Selecciona un departamento válido.',
            'checkout_intent.in' => 'Selecciona si deseas cotizar o pagar.',
            'payment_method.required' => 'Selecciona un método de pago.',
            'payment_method.in' => 'Método de pago no válido.',
        ];
    }
}
