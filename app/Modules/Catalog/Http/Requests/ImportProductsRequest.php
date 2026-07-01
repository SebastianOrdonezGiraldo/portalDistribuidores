<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportProductsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('default_action')) {
            $this->merge([
                'default_action' => strtolower((string) $this->input('default_action')),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
                'max:10240',
            ],
            'default_action' => [
                'nullable',
                'string',
                Rule::in(['upsert', 'create', 'update']),
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file' => [
                'description' => 'Archivo CSV o TXT con los productos a importar.',
                'example' => null,
            ],
            'default_action' => [
                'description' => 'Accion por defecto para filas sin accion explicita: upsert, create o update.',
                'example' => 'upsert',
            ],
        ];
    }
}
