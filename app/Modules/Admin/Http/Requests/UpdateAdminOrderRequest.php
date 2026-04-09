<?php

namespace App\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:0', 'max:999999'],
            'items.*.unit_label' => ['required', 'string', 'max:40'],
            'new_items' => ['nullable', 'array'],
            'new_items.*.catalog_ref' => ['nullable', 'string', 'regex:/^(p|v):\d+$/'],
            'new_items.*.qty' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'new_items.*.unit_label' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $newItems = (array) $this->input('new_items', []);

            foreach ($newItems as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $catalogRef = trim((string) ($row['catalog_ref'] ?? ''));
                $qty = isset($row['qty']) ? (int) $row['qty'] : null;
                $unitLabel = trim((string) ($row['unit_label'] ?? ''));

                $hasAnyData = $catalogRef !== '' || $qty !== null || $unitLabel !== '';
                if (! $hasAnyData) {
                    continue;
                }

                if ($catalogRef === '') {
                    $validator->errors()->add("new_items.{$index}.catalog_ref", 'Selecciona un producto para agregar.');
                }

                if ($qty === null || $qty <= 0) {
                    $validator->errors()->add("new_items.{$index}.qty", 'La cantidad del producto nuevo debe ser mayor a cero.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_nit.regex' => 'El NIT/Cédula debe contener solo números.',
            'department.in' => 'Selecciona un departamento válido.',
            'items.required' => 'Debes enviar los ítems de la cotización.',
            'items.min' => 'La cotización debe incluir al menos un ítem.',
            'items.*.qty.min' => 'La cantidad no puede ser negativa.',
            'new_items.*.catalog_ref.regex' => 'Selecciona un producto válido para agregar.',
        ];
    }
}
