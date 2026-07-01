<?php

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantities' => ['required', 'array', 'max:200'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'quantities' => [
                'description' => 'Mapa de lineas del carrito y su nueva cantidad. Enviar 0 elimina la linea.',
                'example' => ['12-25' => 5],
            ],
            'quantities.*' => [
                'description' => 'Cantidad nueva para la linea indicada.',
                'example' => 5,
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (array_keys((array) $this->input('quantities', [])) as $lineKey) {
                if (preg_match('/^\d+-\d+$/', (string) $lineKey) !== 1) {
                    $validator->errors()->add('quantities', 'Formato de línea de carrito no valido.');

                    return;
                }
            }
        });
    }
}
