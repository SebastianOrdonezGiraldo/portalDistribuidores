<?php

namespace App\Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Nombre interno de la sucursal.',
                'example' => 'Principal',
            ],
            'address' => [
                'description' => 'Direccion de la sucursal.',
                'example' => 'Calle 10 #20-30',
            ],
            'city' => [
                'description' => 'Ciudad de la sucursal.',
                'example' => 'Bogota',
            ],
            'is_default' => [
                'description' => 'Marca la sucursal como predeterminada para la empresa.',
                'example' => true,
            ],
        ];
    }
}
